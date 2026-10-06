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

use Carbon\Carbon;
use Closure;
use OpenDxp\Model\DataObject\Data;
use OpenDxp\Tests\Factory\TestObjectFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

it('keeps a localized field apart per language', function (string $field, Closure $german, Closure $english) {
    $germanValue = $german();
    $englishValue = $english();

    $object = UnittestFactory::new()
        ->withLocalizedValues(
            $field,
            [
                'de' => $germanValue,
                'en' => $englishValue,
            ],
        )
        ->create();

    expect(reloaded($object))
        ->toCarryLocalizedField($field, $germanValue, 'de')
        ->toCarryLocalizedField($field, $englishValue, 'en');
})->with([
    'a line of text' => [
        'linput',
        fn () => 'German text',
        fn () => 'English text',
    ],
    'a text area' => [
        'ltextarea',
        fn () => 'German text',
        fn () => 'English text',
    ],
    'formatted text' => [
        'lwysiwyg',
        fn () => 'German text',
        fn () => 'English text',
    ],
    'a number' => [
        'lnumber',
        fn () => 124,
        fn () => 125,
    ],
    'a slider' => [
        'lslider',
        fn () => 8,
        fn () => 9,
    ],
    'a checkbox' => [
        'lcheckbox',
        fn () => true,
        fn () => false,
    ],
    'a select' => [
        'lselect',
        fn () => '2',
        fn () => '1',
    ],
    'several selected values' => [
        'lmultiselect',
        fn () => [
            '1',
            '2',
        ],
        fn () => ['2'],
    ],
    'several countries' => [
        'lcountries',
        fn () => [
            '1',
            '2',
        ],
        fn () => ['2'],
    ],
    'several languages' => [
        'llanguages',
        fn () => [
            '1',
            '2',
        ],
        fn () => ['2'],
    ],
    'a date' => [
        'ldate',
        fn () => Carbon::create(2000, 12, 24),
        fn () => Carbon::create(2000, 12, 25),
    ],
    'a date and a time' => [
        'ldatetime',
        fn () => Carbon::create(2000, 12, 24),
        fn () => Carbon::create(2000, 12, 25),
    ],
    'a time of day' => [
        'ltime',
        fn () => '06:41',
        fn () => '07:41',
    ],
    'a table' => [
        'ltable',
        fn () => [
            [
                'eins',
                'zwei',
            ],
            [
                1,
                2,
            ],
        ],
        fn () => [
            [
                'one',
                'two',
            ],
            [
                1,
                2,
            ],
        ],
    ],
    'an image' => [
        'limage',
        fn () => image('german.jpg'),
        fn () => image('english.jpg'),
    ],
    'a link to a document' => [
        'llink',
        fn () => linkTo(page('german')),
        fn () => linkTo(page('english')),
    ],
    'a slug' => [
        'lurlSlug',
        fn () => [new Data\UrlSlug('/de/content')],
        fn () => [new Data\UrlSlug('/en/content')],
    ],
    'the objects it relates to' => [
        'lobjects',
        fn () => TestObjectFactory::createMany(3),
        fn () => TestObjectFactory::createMany(2),
    ],
    'the elements it relates to lazily' => [
        'lmultihrefLazy',
        fn () => TestObjectFactory::createMany(3),
        fn () => TestObjectFactory::createMany(2),
    ],
]);

it('tells two texts apart that only look like the same number', function () {
    $object = UnittestFactory::new()
        ->withLocalizedValues(
            'linput',
            [
                'en' => '0001',
                'de' => '0.1000',
            ],
        )
        ->create();
    $definition = $object->getClass()->getFieldDefinition('localizedfields')->getFieldDefinition('linput');

    $loaded = reloaded($object);
    $englishIsEqual = $definition->isEqual($loaded->getLinput('en'), '000001');
    $germanIsEqual = $definition->isEqual($loaded->getLinput('de'), '0.100000');

    expect($englishIsEqual)
        ->toBeFalse()
        ->and($germanIsEqual)
        ->toBeFalse();
});
