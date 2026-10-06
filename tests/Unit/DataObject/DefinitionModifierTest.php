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

use Closure;
use OpenDxp\Model\DataObject\ClassDefinition\Data\Input;
use OpenDxp\Model\DataObject\ClassDefinition\Layout;
use OpenDxp\Model\DataObject\ClassDefinition\Layout\Panel;
use OpenDxp\Model\DataObject\DefinitionModifier;

function panel(string $name, array $children): Panel
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

function childNames(Layout $layout): array
{
    return array_map(static fn ($child) => $child->getName(), $layout->getChildren());
}

beforeEach(function () {
    $this->modifier = new DefinitionModifier();
    $this->inner = panel('inner', [input('inside')]);
    $this->layout = panel('root', [
        input('before'),
        $this->inner,
        input('after'),
    ]);
});

it('puts fields after the one it appends to', function () {
    $modified = $this->modifier->appendFields(
        $this->layout,
        'inner',
        [
            input('first'),
            input('second'),
        ],
    );

    expect($modified)
        ->toBeTrue()
        ->and(childNames($this->layout))
        ->toBe([
            'before',
            'inner',
            'first',
            'second',
            'after',
        ]);
});

it('puts fields before the one it prepends to', function () {
    $modified = $this->modifier->prependFields(
        $this->layout,
        'inner',
        [
            input('first'),
            input('second'),
        ],
    );

    expect($modified)
        ->toBeTrue()
        ->and(childNames($this->layout))
        ->toBe([
            'before',
            'first',
            'second',
            'inner',
            'after',
        ]);
});

it('puts fields where the one it replaces was', function () {
    $modified = $this->modifier->replaceField(
        $this->layout,
        'inner',
        [input('instead')],
    );

    expect($modified)
        ->toBeTrue()
        ->and(childNames($this->layout))
        ->toBe([
            'before',
            'instead',
            'after',
        ]);
});

it('removes a field', function () {
    $modified = $this->modifier->removeField($this->layout, 'inner');

    expect($modified)
        ->toBeTrue()
        ->and(childNames($this->layout))
        ->toBe([
            'before',
            'after',
        ]);
});

it('puts fields at the front of the panel it names', function () {
    $modified = $this->modifier->insertFieldsFront(
        $this->layout,
        'inner',
        [
            input('first'),
            input('second'),
        ],
    );

    expect($modified)
        ->toBeTrue()
        ->and(childNames($this->inner))
        ->toBe([
            'first',
            'second',
            'inside',
        ]);
});

it('puts fields at the back of the panel it names', function () {
    $modified = $this->modifier->insertFieldsBack(
        $this->layout,
        'inner',
        [
            input('first'),
            input('second'),
        ],
    );

    expect($modified)
        ->toBeTrue()
        ->and(childNames($this->inner))
        ->toBe([
            'inside',
            'first',
            'second',
        ]);
});

it('inserts nothing into a field', function (Closure $insert) {
    $modified = $insert($this->modifier, $this->layout);

    expect($modified)->toBeFalse();
})->with([
    'at the front' => fn (DefinitionModifier $modifier, Layout $layout) => $modifier->insertFieldsFront(
        $layout,
        'before',
        [input('first')],
    ),
    'at the back' => fn (DefinitionModifier $modifier, Layout $layout) => $modifier->insertFieldsBack(
        $layout,
        'before',
        [input('first')],
    ),
]);

it('changes nothing for a name the layout does not hold', function (Closure $modify) {
    $modified = $modify($this->modifier, $this->layout);

    expect($modified)
        ->toBeFalse()
        ->and(childNames($this->layout))
        ->toBe([
            'before',
            'inner',
            'after',
        ]);
})->with([
    'appending' => fn (DefinitionModifier $modifier, Layout $layout) => $modifier->appendFields(
        $layout,
        'nowhere',
        [input('first')],
    ),
    'prepending' => fn (DefinitionModifier $modifier, Layout $layout) => $modifier->prependFields(
        $layout,
        'nowhere',
        [input('first')],
    ),
    'replacing' => fn (DefinitionModifier $modifier, Layout $layout) => $modifier->replaceField(
        $layout,
        'nowhere',
        [input('first')],
    ),
    'removing' => fn (DefinitionModifier $modifier, Layout $layout) => $modifier->removeField($layout, 'nowhere'),
    'inserting at the front' => fn (DefinitionModifier $modifier, Layout $layout) => $modifier->insertFieldsFront(
        $layout,
        'nowhere',
        [input('first')],
    ),
    'inserting at the back' => fn (DefinitionModifier $modifier, Layout $layout) => $modifier->insertFieldsBack(
        $layout,
        'nowhere',
        [input('first')],
    ),
]);
