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

use Carbon\Carbon;
use OpenDxp\Model\DataObject\ClassDefinition\Data as Definition;
use OpenDxp\Model\DataObject\Data;
use OpenDxp\Model\User;

dataset('normalizable values', [
    'a boolean select' => [fn () => new Definition\BooleanSelect(), fn () => true],
    'a checkbox' => [fn () => new Definition\Checkbox(), fn () => true],
    'a consent' => [fn () => new Definition\Consent(), fn () => new Data\Consent(true)],
    'a country' => [fn () => new Definition\Country(), fn () => 'de'],
    'several countries' => [fn () => new Definition\Countrymultiselect(), fn () => ['de', 'en']],
    'a date' => [fn () => new Definition\Date(), fn () => Carbon::createFromTimestamp(1700000000)],
    'a date and a time' => [fn () => new Definition\Datetime(), fn () => Carbon::createFromTimestamp(1700000000)],
    'an email address' => [fn () => new Definition\Email(), fn () => 'john@doe.com'],
    'a url of an image elsewhere' => [fn () => new Definition\ExternalImage(), fn () => new Data\ExternalImage('https://someurl.com')],
    'a first name' => [fn () => new Definition\Firstname(), fn () => 'john'],
    'a gender' => [fn () => new Definition\Gender(), fn () => 'male'],
    'an area of the globe' => [fn () => new Definition\Geobounds(), fn () => owned(new Data\Geobounds(
        new Data\GeoCoordinates(123, -120),
        new Data\GeoCoordinates(456, 130),
    ))],
    'a point on the globe' => [fn () => new Definition\Geopoint(), fn () => owned(new Data\GeoCoordinates(123, 56))],
    'a polygon on the globe' => [fn () => new Definition\Geopolygon(), fn () => [
        owned(new Data\GeoCoordinates(123, -120)),
        owned(new Data\GeoCoordinates(50, 70)),
        owned(new Data\GeoCoordinates(56, 130)),
    ]],
    'a line across the globe' => [fn () => new Definition\Geopolyline(), fn () => [
        owned(new Data\GeoCoordinates(123, -120)),
        owned(new Data\GeoCoordinates(50, 70)),
        owned(new Data\GeoCoordinates(56, 130)),
    ]],
    'an image with hotspots' => [fn () => new Definition\Hotspotimage(), fn () => croppedImage(0)],
    'an image' => [fn () => new Definition\Image(), fn () => anImage('image.jpg')],
    'a gallery of images' => [fn () => new Definition\ImageGallery(), fn () => new Data\ImageGallery([
        croppedImage(0), croppedImage(1), croppedImage(2),
    ])],
    'a line of text' => [fn () => new Definition\Input(), fn () => 'some text'],
    'a link' => [fn () => new Definition\Link(), fn () => aLinkToAnObject()],
    'several selected values' => [fn () => new Definition\Multiselect(), fn () => ['A', 'B', 'C']],
    'a number' => [fn () => new Definition\Numeric(), fn () => 123.1],
    'a password' => [fn () => new Definition\Password(), fn () => 'mysecret'],
    'a colour' => [fn () => new Definition\RgbaColor(), fn () => new Data\RgbaColor(1, 2, 3, 12)],
    'a select' => [fn () => new Definition\Select(), fn () => 'Z'],
    'a slider' => [fn () => new Definition\Slider(), fn () => 77],
    'a structured table' => [fn () => new Definition\StructuredTable(), fn () => aStructuredTable()],
    'a table' => [fn () => new Definition\Table(), fn () => [['A', 'B', 'C'], ['E', 'F', 'G']]],
    'a text area' => [fn () => new Definition\Textarea(), fn () => "some text\nover two lines"],
    'a time of day' => [fn () => new Definition\Time(), fn () => '01:23'],
    'several slugs' => [fn () => new Definition\UrlSlug(), fn () => [
        new Data\UrlSlug('/abc', 1),
        new Data\UrlSlug('/ebf', 2),
    ]],
    'a user' => [fn () => new Definition\User(), fn () => User::getByName('admin')->getId()],
    'a video' => [fn () => new Definition\Video(), fn () => aVideoWithPoster()],
    'formatted text' => [fn () => new Definition\Wysiwyg(), fn () => 'some text<br />over two lines'],
]);
