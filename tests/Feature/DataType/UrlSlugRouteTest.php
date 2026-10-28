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

use OpenDxp\Test\Factory\SiteFactory;
use OpenDxp\Tests\Application\Service\AvailableSitesListener;
use OpenDxp\Tests\Factory\SluggableFactory;

beforeEach(function () {
    $this->bike = SluggableFactory::new()
        ->withLocalizedValues(
            'lname',
            [
                'de' => 'Gruenes Rad',
                'en' => 'Green Bike',
            ],
        )
        ->create();
});

it('routes the slug of every available language', function (string $path) {
    $objectId = findRoutedObjectId($path);

    expect($objectId)->toBe($this->bike->getId());
})->with([
    'German' => ['/de/things/gruenes-rad'],
    'English' => ['/en/things/green-bike'],
]);

it('finds no route for the slug of a language that is no longer available', function () {
    $listener = (new AvailableSitesListener())
        ->makeUnavailable('de');

    $objectId = runWithAvailableSitesListener($listener, fn () => findRoutedObjectId('/de/things/gruenes-rad'));

    expect($objectId)->toBeNull();
});

it('finds no route for the slug of a language on a site that does not offer it', function () {
    $site = SiteFactory::createOne();
    $listener = (new AvailableSitesListener())
        ->removeSite($site, 'de');

    $objectId = runWithAvailableSitesListener($listener, fn () => findRoutedObjectId('/de/things/gruenes-rad', $site));

    expect($objectId)->toBeNull();
});

it('routes the slug of a language on a site that offers it', function () {
    $site = SiteFactory::createOne();
    $listener = (new AvailableSitesListener())
        ->removeSite($site, 'de');

    $objectId = runWithAvailableSitesListener($listener, fn () => findRoutedObjectId('/en/things/green-bike', $site));

    expect($objectId)->toBe($this->bike->getId());
});
