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

use OpenDxp\Db;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Data\InputQuantityValue;
use OpenDxp\Model\Element\ValidationException;
use OpenDxp\Test\Factory\QuantityValueUnitFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

/**
 * @return array<string, mixed>
 */
function storedRow(Concrete $object): array
{
    return Db::get()->fetchAssociative(
        sprintf('SELECT * FROM object_store_%s WHERE oo_id = ?', $object->getClassId()),
        [$object->getId()],
    );
}

it('writes the default value of a field into the version', function () {
    $object = UnittestFactory::createOne();

    $versioned = $object->getLatestVersion(includingPublished: true)->getData();

    expect($versioned)
        ->getInputWithDefault()
        ->toBe('default')
        ->getMandatoryInputWithDefault()
        ->toBe('default');
});

it('stores an empty string as an empty string', function () {
    $unit = QuantityValueUnitFactory::createOne();
    $object = UnittestFactory::createOne([
        'input' => 'InputValue',
        'textarea' => 'TextareaValue',
        'wysiwyg' => 'WysiwygValue',
        'password' => 'PasswordValue',
        'inputQuantityValue' => new InputQuantityValue('1', $unit->getId()),
    ]);

    $object->setInput('');
    $object->setTextarea('');
    $object->setWysiwyg('');
    $object->setPassword('');
    $object->setInputQuantityValue(new InputQuantityValue('', ''));
    $object->save();

    expect(storedRow($object))->toMatchArray([
        'input' => '',
        'textarea' => '',
        'wysiwyg' => '',
        'inputQuantityValue__value' => '',
        // A password is hashed on save, and nothing hashes to an empty string.
        'password' => null,
    ]);
});

it('stores null as null', function () {
    $unit = QuantityValueUnitFactory::createOne();
    $object = UnittestFactory::createOne([
        'input' => 'InputValue',
        'textarea' => 'TextareaValue',
        'wysiwyg' => 'WysiwygValue',
        'password' => 'PasswordValue',
        'inputQuantityValue' => new InputQuantityValue('1', $unit->getId()),
    ]);

    $object->setInput(null);
    $object->setTextarea(null);
    $object->setWysiwyg(null);
    $object->setPassword(null);
    $object->setInputQuantityValue(null);
    $object->save();

    expect(storedRow($object))->toMatchArray([
        'input' => null,
        'textarea' => null,
        'wysiwyg' => null,
        'password' => null,
        'inputQuantityValue__value' => null,
    ]);
});

it('strips a script from formatted text before it is stored', function () {
    $object = UnittestFactory::createOne([
        'wysiwyg' => '!@#$%^abc\'"<script>console.log("ops");</script> 测试&lt; edf &gt; "',
    ]);

    $loaded = html_entity_decode(reloaded($object)->getWysiwyg());
    $queried = html_entity_decode(queryTableValue($object, 'wysiwyg'));

    expect($loaded)
        ->toBe('!@#$%^abc\'" 测试< edf > "')
        ->and($queried)
        ->toBe('!@#$%^abc\'" 测试< edf > "');
});

it('refuses a value longer than its column', function () {
    $object = UnittestFactory::new()
        ->unsaved()
        ->create(['input' => str_repeat('x', 500)]);

    $object->save();
})->throws(ValidationException::class, 'Value in field [ input ] is longer than 190 characters');
