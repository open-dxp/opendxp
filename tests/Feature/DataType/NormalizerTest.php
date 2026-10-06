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
use OpenDxp\Model\DataObject\ClassDefinition\Data as Definition;
use OpenDxp\Model\DataObject\Data;
use OpenDxp\Model\User;
use OpenDxp\Normalizer\NormalizerInterface;
use OpenDxp\Tests\Factory\TestObjectFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

/**
 * A normalizer sets this owner on the value it builds, so the value going in carries the same one.
 */
const OWNER = [
    'owner' => 'dummy owner',
    'fieldname' => 'dummy field',
    'language' => 'en',
];

/**
 * @template T of object
 *
 * @param T $value
 *
 * @return T
 */
function owned(object $value): object
{
    $value->_setOwner(OWNER['owner']);
    $value->_setOwnerFieldname(OWNER['fieldname']);
    $value->_setOwnerLanguage(OWNER['language']);

    return $value;
}

function croppedImage(int $index): Data\Hotspotimage
{
    $image = new Data\Hotspotimage();
    $image->setImage(image(sprintf('cropped%d.jpg', $index)));
    $image->setCrop([
        'cropWidth' => 60 + $index,
        'cropHeight' => 78 + $index,
        'cropTop' => 4.1 + $index,
        'cropLeft' => 4.2 + $index,
        'cropPercent' => true,
    ]);
    $image->setMarker([
        [
            'top' => 56 + $index,
            'left' => 62 + $index,
        ],
    ]);

    return $image;
}

function linkToAnObject(): Data\Link
{
    $link = new Data\Link();
    $link->setInternalType('object');
    $link->setInternal(TestObjectFactory::createOne()->getId());
    $link->setTarget('_blank');
    $link->setTitle('sometitle');

    return $link;
}

it('reads a value back out of its normalized form', function (NormalizerInterface $definition, Closure $value) {
    $original = $value();

    $normalized = $definition->normalize($original);
    $back = $definition->denormalize($normalized, OWNER);

    expect($back)->toEqual($original);
})->with([
    'a boolean select' => [
        fn () => new Definition\BooleanSelect(),
        fn () => true,
    ],
    'a checkbox' => [
        fn () => new Definition\Checkbox(),
        fn () => true,
    ],
    'a consent' => [
        fn () => new Definition\Consent(),
        fn () => new Data\Consent(true),
    ],
    'a country' => [
        fn () => new Definition\Country(),
        fn () => 'de',
    ],
    'several countries' => [
        fn () => new Definition\Countrymultiselect(),
        fn () => [
            'de',
            'en',
        ],
    ],
    'a date' => [
        fn () => new Definition\Date(),
        fn () => Carbon::createFromTimestamp(1700000000),
    ],
    'a date and a time' => [
        fn () => new Definition\Datetime(),
        fn () => Carbon::createFromTimestamp(1700000000),
    ],
    'an email address' => [
        fn () => new Definition\Email(),
        fn () => 'john@doe.com',
    ],
    'a url of an image elsewhere' => [
        fn () => new Definition\ExternalImage(),
        fn () => new Data\ExternalImage('https://someurl.com'),
    ],
    'a first name' => [
        fn () => new Definition\Firstname(),
        fn () => 'john',
    ],
    'a gender' => [
        fn () => new Definition\Gender(),
        fn () => 'male',
    ],
    'an area of the globe' => [
        fn () => new Definition\Geobounds(),
        fn () => owned(new Data\Geobounds(
            new Data\GeoCoordinates(123, -120),
            new Data\GeoCoordinates(456, 130),
        )),
    ],
    'a point on the globe' => [
        fn () => new Definition\Geopoint(),
        fn () => owned(new Data\GeoCoordinates(123, 56)),
    ],
    'a polygon on the globe' => [
        fn () => new Definition\Geopolygon(),
        fn () => [
            owned(new Data\GeoCoordinates(123, -120)),
            owned(new Data\GeoCoordinates(50, 70)),
            owned(new Data\GeoCoordinates(56, 130)),
        ],
    ],
    'a line across the globe' => [
        fn () => new Definition\Geopolyline(),
        fn () => [
            owned(new Data\GeoCoordinates(123, -120)),
            owned(new Data\GeoCoordinates(50, 70)),
            owned(new Data\GeoCoordinates(56, 130)),
        ],
    ],
    'an image with hotspots' => [
        fn () => new Definition\Hotspotimage(),
        fn () => croppedImage(0),
    ],
    'an image' => [
        fn () => new Definition\Image(),
        fn () => image('image.jpg'),
    ],
    'a gallery of images' => [
        fn () => new Definition\ImageGallery(),
        fn () => new Data\ImageGallery([
            croppedImage(0),
            croppedImage(1),
            croppedImage(2),
        ]),
    ],
    'a line of text' => [
        fn () => new Definition\Input(),
        fn () => 'some text',
    ],
    'a link' => [
        fn () => new Definition\Link(),
        fn () => linkToAnObject(),
    ],
    'several selected values' => [
        fn () => new Definition\Multiselect(),
        fn () => [
            'A',
            'B',
            'C',
        ],
    ],
    'a number' => [
        fn () => new Definition\Numeric(),
        fn () => 123.1,
    ],
    'a password' => [
        fn () => new Definition\Password(),
        fn () => 'mysecret',
    ],
    'a colour' => [
        fn () => new Definition\RgbaColor(),
        fn () => new Data\RgbaColor(1, 2, 3, 12),
    ],
    'a select' => [
        fn () => new Definition\Select(),
        fn () => 'Z',
    ],
    'a slider' => [
        fn () => new Definition\Slider(),
        fn () => 77,
    ],
    'a structured table' => [
        fn () => new Definition\StructuredTable(),
        fn () => new Data\StructuredTable([
            'row1' => [
                'col1' => '1',
                'col2' => '2',
            ],
            'row2' => [
                'col1' => '3',
                'col2' => '4',
            ],
        ]),
    ],
    'a table' => [
        fn () => new Definition\Table(),
        fn () => [
            [
                'A',
                'B',
                'C',
            ],
            [
                'E',
                'F',
                'G',
            ],
        ],
    ],
    'a text area' => [
        fn () => new Definition\Textarea(),
        fn () => "some text\nover two lines",
    ],
    'a time of day' => [
        fn () => new Definition\Time(),
        fn () => '01:23',
    ],
    'several slugs' => [
        fn () => new Definition\UrlSlug(),
        fn () => [
            new Data\UrlSlug('/abc', 1),
            new Data\UrlSlug('/ebf', 2),
        ],
    ],
    'a user' => [
        fn () => new Definition\User(),
        fn () => User::getByName('admin')->getId(),
    ],
    'a video' => [
        fn () => new Definition\Video(),
        fn () => video(image('clip.jpg')),
    ],
    'formatted text' => [
        fn () => new Definition\Wysiwyg(),
        fn () => 'some text<br />over two lines',
    ],
]);

it('normalizes a date to its timestamp', function (Definition $definition) {
    $date = Carbon::createFromTimestamp(1700000000);

    $normalized = $definition->normalize($date);

    expect($normalized)->toBe(1700000000);
})->with([
    'a date' => fn () => new Definition\Date(),
    'a date and a time' => fn () => new Definition\Datetime(),
]);

it('reads a quantity back with its value and its unit', function (Definition $definition, object $original) {
    $normalized = $definition->normalize($original);
    $back = $definition->denormalize($normalized);

    expect($back)
        ->toBeInstanceOf($original::class)
        ->getValue()
        ->toEqual($original->getValue())
        ->getUnitId()
        ->toBe($original->getUnitId());
})->with([
    'a quantity' => [
        fn () => new Definition\QuantityValue(),
        fn () => new Data\QuantityValue(123.4, quantityUnit('cm')),
    ],
    'a quantity written as text' => [
        fn () => new Definition\InputQuantityValue(),
        fn () => new Data\InputQuantityValue('123', quantityUnit('cm')),
    ],
]);

it('reads a relation back as the elements it pointed at', function (Definition $definition, array $elements) {
    $normalized = $definition->normalize($elements);
    $back = $definition->denormalize($normalized);

    expect($back)->toEqual($elements);
})->with([
    'objects' => [
        fn () => new Definition\ManyToManyObjectRelation(),
        fn () => TestObjectFactory::createMany(2),
    ],
    'elements of any kind' => [
        fn () => new Definition\ManyToManyRelation(),
        fn () => [
            TestObjectFactory::createOne(),
            image('image.jpg'),
        ],
    ],
]);

it('reads a single relation back as the element it pointed at', function () {
    $definition = new Definition\ManyToOneRelation();
    $target = TestObjectFactory::createOne();

    $normalized = $definition->normalize($target);
    $back = $definition->denormalize($normalized);

    expect($back)->getId()->toBe($target->getId());
});

it('reads the localized fields back with the values of every language', function () {
    $target = TestObjectFactory::createOne();
    $object = UnittestFactory::new()
        ->withLocalizedValues(
            'linput',
            ['en' => '123'],
        )
        ->withLocalizedValues(
            'lobjects',
            ['en' => [$target]],
        )
        ->create();
    $definition = $object->getClass()->getFieldDefinition('localizedfields');

    $normalized = $definition->normalize($object->getLocalizedfields());
    // A localized field reads the languages off the object it belongs to, so denormalizing needs it.
    $back = $definition->denormalize(
        $normalized,
        ['object' => $object],
    );

    expect($back->getLocalizedValue('linput'))
        ->toBe('123')
        ->and($back->getLocalizedValue('lobjects')[0]->getId())
        ->toBe($target->getId());
});
