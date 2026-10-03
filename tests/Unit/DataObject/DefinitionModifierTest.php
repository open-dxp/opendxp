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


namespace OpenDxp\Tests\Unit\DataObject;

use OpenDxp\Model\DataObject\ClassDefinition\Data\Input;
use OpenDxp\Model\DataObject\ClassDefinition\Layout;
use OpenDxp\Model\DataObject\ClassDefinition\Layout\Panel;
use OpenDxp\Model\DataObject\DefinitionModifier;

function panel(string $name, array $children = []): Panel
{
    $panel = new Panel();
    $panel->setName($name);
    $panel->setChildren($children);

    return $panel;
}

function input(string $name): Input
{
    $field = new Input();
    $field->setName($name);

    return $field;
}

/**
 * A layout of a panel that holds a field, another panel beside it, and a field after that. Every test
 * builds its own, because the modifier changes the tree it is handed.
 */
function aLayout(): Panel
{
    return panel('root', [
        input('before'),
        panel('inner', [input('inside')]),
        input('after'),
    ]);
}

/**
 * Finds a name among the children of the layout, searching the panels below it as well. The modifier
 * works by name, so the tests read the result the same way.
 */
function indexOf(Layout $layout, string $name): int
{
    foreach ($layout->getChildren() as $index => $child) {
        if ($child->getName() === $name) {
            return $index;
        }
    }

    foreach ($layout->getChildren() as $child) {
        if ($child instanceof Layout && ($found = indexOf($child, $name)) >= 0) {
            return $found;
        }
    }

    return -1;
}

function childNames(Layout $layout): array
{
    return array_map(static fn ($child) => $child->getName(), $layout->getChildren());
}

beforeEach(function () {
    $this->modifier = new DefinitionModifier();
    $this->layout = aLayout();
});

it('puts a field after the one it was told to append to', function () {

    expect($this->modifier->appendFields($this->layout, 'inner', [input('first'), input('second')]))->toBeTrue();

    expect(childNames($this->layout))->toBe(['before', 'inner', 'first', 'second', 'after']);
});

it('puts a field before the one it was told to prepend to', function () {

    expect($this->modifier->prependFields($this->layout, 'inner', [input('first'), input('second')]))->toBeTrue();

    expect(childNames($this->layout))->toBe(['before', 'first', 'second', 'inner', 'after']);
});

it('puts a field where the one it replaced sat', function () {

    expect($this->modifier->replaceField($this->layout, 'inner', [input('instead')]))->toBeTrue();

    expect(childNames($this->layout))->toBe(['before', 'instead', 'after']);
});

it('takes a field out', function () {

    expect($this->modifier->removeField($this->layout, 'inner'))->toBeTrue();

    expect(childNames($this->layout))->toBe(['before', 'after']);
});

it('puts a field at the front of the panel it names', function () {

    expect($this->modifier->insertFieldsFront($this->layout, 'inner', [input('first'), input('second')]))->toBeTrue();

    expect(childNames($this->layout->getChildren()[1]))->toBe(['first', 'second', 'inside']);
});

it('puts a field at the back of the panel it names', function () {

    expect($this->modifier->insertFieldsBack($this->layout, 'inner', [input('first'), input('second')]))->toBeTrue();

    expect(childNames($this->layout->getChildren()[1]))->toBe(['inside', 'first', 'second']);
});

it('refuses to insert into a field, because only a panel holds children', function (string $operation) {

    expect($this->modifier->{$operation}($this->layout, 'before', [input('first')]))->toBeFalse();
})->with(['insertFieldsFront', 'insertFieldsBack']);

it('changes nothing for a name the layout does not hold', function (string $operation) {

    expect($this->modifier->{$operation}($this->layout, 'nowhere', [input('first')]))
        ->toBeFalse()
        ->and(childNames($this->layout))
        ->toBe(['before', 'inner', 'after']);
})->with([
    'appendFields', 'prependFields', 'replaceField', 'removeField', 'insertFieldsFront', 'insertFieldsBack',
]);
