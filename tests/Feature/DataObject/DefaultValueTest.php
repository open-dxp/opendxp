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

namespace OpenDxp\Tests\Feature\DataObject;

use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Test\Elements;
use OpenDxp\Tests\Factory\UnittestFactory;

function setSelectDefaultValue(?string $defaultValue): void
{
    ClassDefinition::getByName('unittest')
        ->getFieldDefinition('select')
        ->setDefaultValue($defaultValue);
}

it('keeps null for a select field without a configured default on create', function () {
    // The class editor stores "no default" as an empty string.
    setSelectDefaultValue('');

    $object = UnittestFactory::createOne();

    expect($object->getSelect())->toBeNull()
        ->and(storedRow($object)['select'])->toBeNull()
        ->and(Elements::refresh($object)->getSelect())->toBeNull()
        ->and($object->getLatestVersion(includingPublished: true)->getData()->getSelect())->toBeNull();
})->after(fn () => setSelectDefaultValue(null));

it('normalizes an explicitly set empty string to null on create', function () {
    setSelectDefaultValue('');

    $object = UnittestFactory::createOne(['select' => '']);

    expect(storedRow($object)['select'])->toBeNull()
        ->and(Elements::refresh($object)->getSelect())->toBeNull();
})->after(fn () => setSelectDefaultValue(null));

it('applies a configured default value on create', function () {
    setSelectDefaultValue('2');

    $object = UnittestFactory::createOne();

    expect($object->getSelect())->toBe('2')
        ->and(storedRow($object)['select'])->toBe('2')
        ->and(Elements::refresh($object)->getSelect())->toBe('2');
})->after(fn () => setSelectDefaultValue(null));
