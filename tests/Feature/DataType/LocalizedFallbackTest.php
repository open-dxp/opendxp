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

namespace OpenDxp\Tests\Feature\DataType;

use Exception;
use OpenDxp\Model\DataObject\Fieldcollection;
use OpenDxp\Model\DataObject\Localizedfield;
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Tests\Factory\UnittestFactory;

function countedWithLocale(string $locale, string $condition): int
{
    $listing = new Unittest\Listing();
    $listing->setLocale($locale);
    $listing->setCondition($condition);

    return count($listing->load());
}

afterEach(fn () => Localizedfield::setStrictMode(false));

it('takes a value with and without a language while strict mode is off', function () {
    $object = UnittestFactory::new()
        ->unsaved()
        ->create();

    $object->setLinput('Test');
    $object->setLinput('TestKo', 'ko');

    expect($object->getLinput())
        ->toBe('Test')
        ->and($object->getLinput('ko'))
        ->toBe('TestKo');
});

it('refuses a value in strict mode', function (?string $language, string $complaint) {
    $object = UnittestFactory::new()
        ->unsaved()
        ->create();
    Localizedfield::setStrictMode(Localizedfield::STRICT_ENABLED);

    expect(fn () => $object->setLinput('Test', $language))->toThrow(Exception::class, $complaint);
})->with([
    'a value that names no language' => [null, 'Language  not accepted in strict mode'],
    'a value in a language the object does not hold' => ['ko', 'Language ko not accepted in strict mode'],
]);

it('keeps a language of a field collection item when another one is written later', function () {
    $item = new Fieldcollection\Data\Unittestfieldcollection();
    $item->setLinput('textEN', 'en');
    $object = UnittestFactory::new()
        ->withFieldcollection(
            'fieldcollection',
            $item,
        )
        ->create();

    $loaded = reloaded($object);
    $loaded->getFieldcollection()->get(0)->setLinput('textDE', 'de');
    $loaded->save();
    $written = reloaded($object)->getFieldcollection()->get(0);

    expect($written->getLinput('en'))
        ->toBe('textEN')
        ->and($written->getLinput('de'))
        ->toBe('textDE');
});

it('returns the value of the fallback language only for an empty field', function (
    string $field,
    mixed $fallback,
    mixed $own,
    mixed $expected,
) {
    $object = UnittestFactory::new()
        ->withLocalizedValues(
            $field,
            [
                'en' => $fallback,
                'de' => $own,
            ],
        )
        ->create();

    $value = reloaded($object)->get($field, 'de');

    expect($value)->toEqual($expected);
})->with([
    'an empty line of text' => ['linput', 'TestEN', '', 'TestEN'],
    'an empty checkbox' => ['lcheckbox', true, null, true],
    'an empty number' => ['lnumber', 123, null, 123],
    'a checkbox that is not ticked' => ['lcheckbox', true, false, false],
    'a number that is zero' => ['lnumber', 123, 0, 0],
]);

it('finds an object in a listing through the fallback language only for an empty field', function (
    string $field,
    mixed $fallback,
    mixed $own,
    string $condition,
    int $expected,
) {
    UnittestFactory::new()
        ->withLocalizedValues(
            $field,
            [
                'en' => $fallback,
                'de' => $own,
            ],
        )
        ->create();

    $count = countedWithLocale('de', $condition);

    expect($count)->toBe($expected);
})->with([
    'an empty checkbox' => ['lcheckbox', true, null, "lcheckbox = '1'", 1],
    'an empty number' => ['lnumber', 123, null, "lnumber = '123'", 1],
    'a checkbox that is not ticked' => ['lcheckbox', true, false, "lcheckbox = '1'", 0],
    'a number that is zero' => ['lnumber', 123, 0, "lnumber = '123'", 0],
]);
