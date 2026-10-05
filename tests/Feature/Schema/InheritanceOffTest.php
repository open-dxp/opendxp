<?php

declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tests\Feature\Schema;

use OpenDxp;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Inheritance;
use OpenDxp\Model\DataObject\Objectbrick\Data\UnittestBrick;
use OpenDxp\Tests\Factory\InheritanceFactory;

function inheritanceBecomes(bool $allowed): void
{
    $class = ClassDefinition::getByName('inheritance');
    $class->setAllowInherit($allowed);
    $class->save();
}

function loaded(DataObject\Concrete $object): Inheritance
{
    return Inheritance::getById($object->getId(), ['force' => true]);
}

beforeEach(function () {
    // Only the admin is handed an object that holds nothing of its own.
    OpenDxp::setAdminMode();
    $this->written = [];
});

afterEach(function () {
    inheritanceBecomes(true);

    foreach (array_reverse($this->written) as $object) {
        $object->delete();
    }
});

it('hands a localized text down no longer once the class forbids inheritance', function () {

    $parent = InheritanceFactory::createOne();
    $parent->setInput('text of the parent in english', 'en');
    $parent->save();

    $child = InheritanceFactory::createOne(['parentId' => $parent->getId()]);
    $this->written = [$parent, $child];

    expect(loaded($child)->getInput('en'))->toBe('text of the parent in english');

    inheritanceBecomes(false);

    // The value is only dropped once the objects are written again.
    loaded($parent)->save();
    loaded($child)->save();

    expect(loaded($child)->getInput('en'))->toBeNull();
});

it('keeps what an object holds of its own when the class forbids inheritance', function () {

    $parent = InheritanceFactory::createOne();

    $brick = new UnittestBrick($parent);
    $brick->setBrickinput('text of the parent');
    $parent->getMybricks()->setUnittestBrick($brick);
    $parent->save();

    $child = InheritanceFactory::createOne(['parentId' => $parent->getId()]);
    $child->getMybricks()->getUnittestBrick()->setBrickinput2('text of the child');
    $child->save();
    $this->written = [$parent, $child];

    inheritanceBecomes(false);

    loaded($parent)->save();
    loaded($child)->save();

    $below = loaded($child)->getMybricks()->getUnittestBrick();

    expect($below->getBrickinput())
        ->toBeNull()
        ->and($below->getBrickinput2())
        ->toBe('text of the child');
});
