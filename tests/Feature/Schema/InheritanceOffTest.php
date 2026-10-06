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

use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Objectbrick\Data\UnittestBrick;
use OpenDxp\Tests\Factory\InheritanceFactory;

function forbidInheritance(): void
{
    $class = ClassDefinition::getByName('inheritance');
    $class->setAllowInherit(false);
    $class->save();
}

beforeEach(fn () => $this->written = []);

afterEach(function () {
    $class = ClassDefinition::getByName('inheritance');
    $class->setAllowInherit(true);
    $class->save();

    foreach (array_reverse($this->written) as $object) {
        $object->delete();
    }
});

it('stops inheriting a localized text once the class forbids it', function () {
    $parent = InheritanceFactory::new()
        ->withLocalizedValues('input', ['en' => 'text of the parent'])
        ->create();
    $child = InheritanceFactory::new()
        ->withParent($parent)
        ->create();
    $this->written = [
        $parent,
        $child,
    ];
    forbidInheritance();

    // The value is only dropped once the objects are written again.
    reloaded($parent)->save();
    reloaded($child)->save();

    expect(reloaded($child)->getInput('en'))->toBeNull();
});

it('keeps what an object holds of its own once the class forbids inheritance', function () {
    $parent = InheritanceFactory::new()
        ->withObjectbrick('mybricks', UnittestBrick::class, ['brickinput' => 'text of the parent'])
        ->create();
    $child = InheritanceFactory::new()
        ->withParent($parent)
        ->withObjectbrick('mybricks', UnittestBrick::class, ['brickinput2' => 'text of the child'])
        ->create();
    $this->written = [
        $parent,
        $child,
    ];
    forbidInheritance();

    reloaded($parent)->save();
    reloaded($child)->save();

    $brick = reloaded($child)->getMybricks()->getUnittestBrick();
    expect($brick->getBrickinput())
        ->toBeNull()
        ->and($brick->getBrickinput2())
        ->toBe('text of the child');
});
