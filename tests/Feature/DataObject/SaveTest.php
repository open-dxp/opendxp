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
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Data\InputQuantityValue;
use OpenDxp\Model\Element\ValidationException;
use OpenDxp\Test\Factory\QuantityValueUnitFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

function storedRow(DataObject\Concrete $object): array
{
    return Db::get()->fetchAssociative(
        sprintf('SELECT * FROM object_store_%s WHERE oo_id = ?', $object->getClassId()),
        [$object->getId()],
    );
}

it('writes the default value of a field into the version it saves', function () {

    $versions = UnittestFactory::createOne()->getVersions();

    expect(end($versions)->getData()->getInputWithDefault())->toBe('default');
});

it('writes the default value of a mandatory field into the version it saves', function () {

    $object = UnittestFactory::new()->unsaved()->create();
    $object->setOmitMandatoryCheck(false);
    $object->save();
    $versions = $object->getVersions();

    expect(end($versions)->getData()->getMandatoryInputWithDefault())->toBe('default');
});

it('keeps an empty string an empty string', function () {

    $object = UnittestFactory::createOne([
        'input' => 'InputValue',
        'textarea' => 'TextareaValue',
        'wysiwyg' => 'WysiwygValue',
        'password' => 'PasswordValue',
        'inputQuantityValue' => new InputQuantityValue('1', QuantityValueUnitFactory::createOne(['abbreviation' => 'km'])->getId()),
    ]);

    $object->setInput('');
    $object->setTextarea('');
    $object->setWysiwyg('');
    $object->setPassword('');
    $object->setInputQuantityValue(new InputQuantityValue('', ''));
    $object->save();

    $stored = storedRow($object);

    expect($stored['input'])
        ->toBe('')
        ->and($stored['textarea'])
        ->toBe('')
        ->and($stored['wysiwyg'])
        ->toBe('')
        ->and($stored['inputQuantityValue__value'])
        ->toBe('')
        // A password is hashed on save, and nothing hashes to an empty string.
        ->and($stored['password'])
        ->toBeNull();
});

it('keeps nothing nothing', function () {

    $object = UnittestFactory::createOne([
        'input' => 'InputValue',
        'textarea' => 'TextareaValue',
        'wysiwyg' => 'WysiwygValue',
        'password' => 'PasswordValue',
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

it('strips the markup from a field before it is stored', function () {

    $object = UnittestFactory::createOne([
        'wysiwyg' => '!@#$%^abc\'"<script>console.log("ops");</script> 测试&lt; edf &gt; "',
    ]);

    $expected = '!@#$%^abc\'" 测试< edf > "';

    $stored = Db::get()->fetchOne(
        sprintf('SELECT `wysiwyg` FROM object_query_%s WHERE oo_id = ?', $object->getClassName()),
        [$object->getId()],
    );

    expect(html_entity_decode(DataObject::getById($object->getId(), ['force' => true])->getWysiwyg()))
        ->toBe($expected)
        ->and(html_entity_decode($stored))
        ->toBe($expected);
});

it('refuses a value longer than its column', function () {

    $object = UnittestFactory::createOne();
    $object->setInput(str_repeat('x', 500));

    $object->save();
})->throws(ValidationException::class);
