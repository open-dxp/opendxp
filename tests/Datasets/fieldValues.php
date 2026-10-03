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
use Carbon\CarbonPeriod;
use OpenDxp\Model\DataObject\Data;

/**
 * Each value is built in a closure, because Pest reads a dataset while it collects the tests, before
 * any element exists.
 */
dataset('field values', [
    'a line of text' => ['input', fn () => 'content1'],
    'a text area' => ['textarea', fn () => 'sometext<br />1'],
    'formatted text' => ['wysiwyg', fn () => 'sometext<br />1'],
    'an email address' => ['email', fn () => 'john@doe.com1'],
    'a first name' => ['firstname', fn () => 'content1'],
    'a last name' => ['lastname', fn () => 'content1'],
    'an encrypted text' => ['encryptedField', fn () => 'content1'],
    'a number' => ['number', fn () => 124],
    'a slider' => ['slider', fn () => 8],
    'a checkbox' => ['checkbox', fn () => true],
    'a boolean select' => ['booleanSelect', fn () => true],
    'a select' => ['select', fn () => '2'],
    'several selected values' => ['multiselect', fn () => ['1', '2']],
    'a gender' => ['gender', fn () => 'female'],
    'a country' => ['country', fn () => 'AU'],
    'several countries' => ['countries', fn () => ['1', '2']],
    'a language' => ['languagex', fn () => 'de'],
    'several languages' => ['languages', fn () => ['1', '2']],
    'a time of day' => ['time', fn () => '06:41'],
    'a date' => ['date', fn () => Carbon::create(2000, 12, 24)],
    'a date and a time' => ['datetime', fn () => Carbon::create(2000, 12, 24)],
    'a range of dates' => ['dateRange', fn () => new CarbonPeriod('2018-04-21', '3 days', '2018-04-27')],
    'a colour' => ['rgbaColor', fn () => new Data\RgbaColor(1, 2, 3, 4)],
    'a url of an image elsewhere' => ['externalImage', fn () => new Data\ExternalImage('someUrl1')],
    'a table' => ['table', fn () => [['eins', 'zwei', 'drei'], [1, 2, 3], ['a', 'b', 'c']]],
    'a point on the globe' => ['point', fn () => new Data\GeoCoordinates(102.25112915039, 2.2008440814678)],
    'an area of the globe' => ['bounds', fn () => new Data\Geobounds(
        new Data\GeoCoordinates(-33.704920213014, 150.60333251953),
        new Data\GeoCoordinates(-33.893217379440, 150.60333251953),
    )],
    'a polygon on the globe' => ['polygon', fn () => geoPolygon()],
    'a line across the globe' => ['polyline', fn () => geoPolygon()],
    'a slug' => ['urlSlug', fn () => [new Data\UrlSlug('/content1')]],
    'a quantity' => ['quantityValue', fn () => new Data\QuantityValue(1001, aUnit('mm'))],
    'a quantity written as text' => ['inputQuantityValue', fn () => new Data\InputQuantityValue('abc1', aUnit('mm'))],
    'the one object it relates to' => ['href', fn () => someObjects(1)[0]],
    'the one object it relates to lazily' => ['lazyHref', fn () => someObjects(1)[0]],
    'the elements it relates to' => ['multihref', fn () => someObjects(4)],
    'the elements it relates to lazily' => ['lazyMultihref', fn () => someObjects(4)],
    'the objects it relates to' => ['objects', fn () => someObjects(4)],
    'the objects it relates to lazily' => ['lazyObjects', fn () => someObjects(4)],
    'an image' => ['image', fn () => anImage('image.jpg')],
    'a user' => ['user', fn () => (string) aUser('unittestdatauser1')->getId()],
    'a link to a document' => ['link', fn () => aLink(aPage('document1'))],
    'an image with hotspots' => ['hotspotimage', fn () => new Data\Hotspotimage(anImage('hotspot.jpg'), hotspots())],
    'a video' => ['video', fn () => aVideoField()],
    'a structured table' => ['structuredtable', fn () => structuredTable()],
]);
