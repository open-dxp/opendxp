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
use OpenDxp\Model\DataObject\Objectbrick\Data\TestDimensions;
use OpenDxp\Test\Elements;
use OpenDxp\Test\Factory\TestProductFactory;

function forbidInheritance(): void
{
    $class = ClassDefinition::getByName('TestProduct');
    $class->setAllowInherit(false);
    $class->save();
}

beforeEach(fn () => $this->written = []);

afterEach(function () {
    $class = ClassDefinition::getByName('TestProduct');
    $class->setAllowInherit(true);
    $class->save();

    foreach (array_reverse($this->written) as $object) {
        $object->delete();
    }
});

it('stops inheriting a localized text once the class forbids it', function () {
    $parent = TestProductFactory::new()
        ->withLocalizedValues('name', ['en' => 'text of the parent'])
        ->create();
    $child = TestProductFactory::new()
        ->withParent($parent)
        ->create();
    $this->written = [
        $parent,
        $child,
    ];
    forbidInheritance();

    // The value is only dropped once the objects are written again.
    Elements::refresh($parent)->save();
    Elements::refresh($child)->save();

    expect(Elements::refresh($child)->getName('en'))->toBeNull();
});

it('keeps what an object holds of its own once the class forbids inheritance', function () {
    $parent = TestProductFactory::new()
        ->withObjectbrick('attributes', TestDimensions::class, ['width' => 'text of the parent'])
        ->create();
    $child = TestProductFactory::new()
        ->withParent($parent)
        ->withObjectbrick('attributes', TestDimensions::class, ['height' => 'text of the child'])
        ->create();
    $this->written = [
        $parent,
        $child,
    ];
    forbidInheritance();

    Elements::refresh($parent)->save();
    Elements::refresh($child)->save();

    $brick = Elements::refresh($child)->getAttributes()->getTestDimensions();
    expect($brick->getWidth())
        ->toBeNull()
        ->and($brick->getHeight())
        ->toBe('text of the child');
});
