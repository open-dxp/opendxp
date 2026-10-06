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
use Carbon\CarbonPeriod;
use Closure;
use OpenDxp\Model\DataObject\Data;
use OpenDxp\Test\Factory\AssetVideoFactory;
use OpenDxp\Tests\Factory\TestObjectFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

/**
 * @return list<Data\GeoCoordinates>
 */
function geoPolygon(): array
{
    return [
        new Data\GeoCoordinates(-33.464671118242684, 150.54428100585938),
        new Data\GeoCoordinates(-33.913733814316245, 150.73654174804688),
        new Data\GeoCoordinates(-33.9946115848146, 151.2542724609375),
    ];
}

/**
 * Both hotspots carry the position of their image in their name.
 *
 * @return list<array<string, int|string>>
 */
function hotspots(int $position): array
{
    return [
        [
            'name' => sprintf('hotspot_%d_1', $position),
            'width' => 10 + $position,
            'height' => 20 + $position,
            'top' => 30 + $position,
            'left' => 40 + $position,
        ],
        [
            'name' => sprintf('hotspot_%d_2', $position),
            'width' => 10 + $position,
            'height' => 50 + $position,
            'top' => 20 + $position,
            'left' => 40 + $position,
        ],
    ];
}

it('keeps the value a field was saved with', function (string $field, Closure $value) {
    $expected = $value();

    $object = UnittestFactory::createOne([$field => $expected]);

    expect(reloaded($object))->toCarryField($field, $expected);
})->with([
    'a line of text' => [
        'input',
        fn () => 'content1',
    ],
    'a text area' => [
        'textarea',
        fn () => 'sometext<br />1',
    ],
    'formatted text' => [
        'wysiwyg',
        fn () => 'sometext<br />1',
    ],
    'an email address' => [
        'email',
        fn () => 'john@doe.com1',
    ],
    'a first name' => [
        'firstname',
        fn () => 'content1',
    ],
    'a last name' => [
        'lastname',
        fn () => 'content1',
    ],
    'an encrypted text' => [
        'encryptedField',
        fn () => 'content1',
    ],
    'a number' => [
        'number',
        fn () => 124,
    ],
    'a slider' => [
        'slider',
        fn () => 8,
    ],
    'a checkbox' => [
        'checkbox',
        fn () => true,
    ],
    'a boolean select' => [
        'booleanSelect',
        fn () => true,
    ],
    'a select' => [
        'select',
        fn () => '2',
    ],
    'several selected values' => [
        'multiselect',
        fn () => [
            '1',
            '2',
        ],
    ],
    'a gender' => [
        'gender',
        fn () => 'female',
    ],
    'a country' => [
        'country',
        fn () => 'AU',
    ],
    'several countries' => [
        'countries',
        fn () => [
            '1',
            '2',
        ],
    ],
    'a language' => [
        'languagex',
        fn () => 'de',
    ],
    'several languages' => [
        'languages',
        fn () => [
            '1',
            '2',
        ],
    ],
    'a time of day' => [
        'time',
        fn () => '06:41',
    ],
    'a date' => [
        'date',
        fn () => Carbon::create(2000, 12, 24),
    ],
    'a date and a time' => [
        'datetime',
        fn () => Carbon::create(2000, 12, 24),
    ],
    'a range of dates' => [
        'dateRange',
        fn () => new CarbonPeriod('2018-04-21', '3 days', '2018-04-27'),
    ],
    'a colour' => [
        'rgbaColor',
        fn () => new Data\RgbaColor(1, 2, 3, 4),
    ],
    'a url of an image elsewhere' => [
        'externalImage',
        fn () => new Data\ExternalImage('someUrl1'),
    ],
    'a table' => [
        'table',
        fn () => [
            [
                'eins',
                'zwei',
                'drei',
            ],
            [
                1,
                2,
                3,
            ],
            [
                'a',
                'b',
                'c',
            ],
        ],
    ],
    'a point on the globe' => [
        'point',
        fn () => new Data\GeoCoordinates(102.25112915039, 2.2008440814678),
    ],
    'an area of the globe' => [
        'bounds',
        fn () => new Data\Geobounds(
            new Data\GeoCoordinates(-33.704920213014, 150.60333251953),
            new Data\GeoCoordinates(-33.893217379440, 150.60333251953),
        ),
    ],
    'a polygon on the globe' => [
        'polygon',
        fn () => geoPolygon(),
    ],
    'a line across the globe' => [
        'polyline',
        fn () => geoPolygon(),
    ],
    'a slug' => [
        'urlSlug',
        fn () => [new Data\UrlSlug('/content1')],
    ],
    'a quantity' => [
        'quantityValue',
        fn () => new Data\QuantityValue(1001, quantityUnit('mm')),
    ],
    'a quantity written as text' => [
        'inputQuantityValue',
        fn () => new Data\InputQuantityValue('abc1', quantityUnit('mm')),
    ],
    'the one object it relates to' => [
        'href',
        fn () => TestObjectFactory::createOne(),
    ],
    'the one object it relates to lazily' => [
        'lazyHref',
        fn () => TestObjectFactory::createOne(),
    ],
    'the elements it relates to' => [
        'multihref',
        fn () => TestObjectFactory::createMany(4),
    ],
    'the elements it relates to lazily' => [
        'lazyMultihref',
        fn () => TestObjectFactory::createMany(4),
    ],
    'the objects it relates to' => [
        'objects',
        fn () => TestObjectFactory::createMany(4),
    ],
    'the objects it relates to lazily' => [
        'lazyObjects',
        fn () => TestObjectFactory::createMany(4),
    ],
    'an image' => [
        'image',
        fn () => image('image.jpg'),
    ],
    'a user' => [
        'user',
        fn () => (string) user('unittestdatauser1')->getId(),
    ],
    'a link to a document' => [
        'link',
        fn () => linkTo(page('document1')),
    ],
    'an image with hotspots' => [
        'hotspotimage',
        fn () => new Data\Hotspotimage(image('hotspot.jpg'), hotspots(0)),
    ],
    'a structured table' => [
        'structuredtable',
        fn () => new Data\StructuredTable([
            'row1' => [
                'col1' => 2,
                'col2' => 'text_a_1',
            ],
            'row2' => [
                'col1' => 3,
                'col2' => 'text_b_1',
            ],
            'row3' => [
                'col1' => 4,
                'col2' => 'text_c_1',
            ],
        ]),
    ],
]);

it('stores a password as a hash and not as the password', function () {
    $object = UnittestFactory::createOne(['password' => 'sEcret$%!1']);

    $stored = reloaded($object)->getPassword();

    expect($stored)
        ->not->toBe('sEcret$%!1')
        ->and(password_verify('sEcret$%!1', $stored))
        ->toBeTrue();
});

it('drops the empty place of a gallery and keeps the images around it', function () {
    $gallery = new Data\ImageGallery([
        new Data\Hotspotimage(image('gal0.jpg'), hotspots(0)),
        null,
        new Data\Hotspotimage(image('gal2.jpg'), hotspots(2)),
    ]);

    $object = UnittestFactory::createOne(['imageGallery' => $gallery]);
    $items = reloaded($object)->getImageGallery()->getItems();

    expect($items)
        ->toHaveCount(2)
        ->and($items[0]->getImage()->getFilename())
        ->toBe('gal0.jpg')
        ->and($items[1]->getImage()->getFilename())
        ->toBe('gal2.jpg')
        ->and($items[1]->getHotspots()[0]['name'])
        ->toBe('hotspot_2_1');
});

it('keeps a video with its poster, title and description', function () {
    $asset = AssetVideoFactory::createOne();

    $object = UnittestFactory::createOne(['video' => video($asset)]);
    $video = reloaded($object)->getVideo();

    expect($video)
        ->getData()
        ->getId()
        ->toBe($asset->getId())
        ->getPoster()
        ->getFilename()
        ->toBe('poster.jpg')
        ->getTitle()
        ->toBe('title')
        ->getDescription()
        ->toBe('description');
});
