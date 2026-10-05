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

use Closure;
use OpenDxp\Model\DataObject\Data\Hotspotimage;
use OpenDxp\Model\DataObject\Data\ImageGallery;
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Tests\Factory\TestObjectFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

// A relation field needs something to point at, and the dataset reads those back out of the database.
beforeEach(fn () => TestObjectFactory::createMany(6));

it('hands a field back with the value it was saved with', function (string $field, Closure $value) {

    $expected = $value();
    $object = UnittestFactory::createOne([$field => $expected]);

    expect(Unittest::getById($object->getId(), ['force' => true]))->toCarryField($field, $expected);
})->with('field values');

it('stores a password as a hash and not as the password', function () {

    $object = UnittestFactory::createOne(['password' => 'sEcret$%!1']);

    $stored = Unittest::getById($object->getId(), ['force' => true])->getPassword();

    expect($stored)
        ->not->toBe('sEcret$%!1')
        ->and(password_get_info($stored)['algo'])
        ->not->toBeNull();
});

it('drops the empty place of a gallery and keeps the images around it', function () {

    $object = UnittestFactory::createOne(['imageGallery' => new ImageGallery([
        new Hotspotimage(anImage('gal0.jpg'), hotspots(0, seed: 1)),
        null,
        new Hotspotimage(anImage('gal2.jpg'), hotspots(2, seed: 1)),
    ])]);

    $items = Unittest::getById($object->getId(), ['force' => true])->getImageGallery()->getItems();

    expect($items)
        ->toHaveCount(2)
        ->and($items[0]->getImage()->getFilename())
        ->toBe('gal0.jpg')
        ->and($items[1]->getImage()->getFilename())
        ->toBe('gal2.jpg')
        ->and($items[1]->getHotspots()[0]['name'])
        ->toBe('hotspot_2_1');
});
