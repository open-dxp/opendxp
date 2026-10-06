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

namespace OpenDxp\Tests\Feature\Element;

it('keeps the note a version was saved with', function (string $element, string $factory) {
    $saved = $factory::createOne();

    $saved->save(['versionNote' => 'a new version of this element']);

    expect($saved->getLatestVersion(includingPublished: true)->getNote())->toBe('a new version of this element');
})->with('elements');

it('stores the user a save names', function (string $element, string $factory) {
    $saved = $factory::createOne();
    $saved->setUserModification(101);

    $saved->save();

    expect(reloaded($saved)->getUserModification())->toBe(101);
})->with('elements');

it('attributes a save without a user to the system user', function (string $element, string $factory) {
    $saved = $factory::createOne(['userModification' => 101]);
    $loaded = reloaded($saved);

    $loaded->save();

    expect(reloaded($loaded)->getUserModification())->toBe(0);
})->with('elements');

it('stores the modification date a save names', function (string $element, string $factory) {
    $saved = $factory::createOne();
    $anHourAgo = time() - 3600;
    $saved->setModificationDate($anHourAgo);

    $saved->save();

    expect(reloaded($saved)->getModificationDate())->toBe($anHourAgo);
})->with('elements');

it('stamps a save without a modification date with the current time', function (string $element, string $factory) {
    $saved = $factory::createOne(['modificationDate' => time() - 3600]);
    $loaded = reloaded($saved);
    $before = time();

    $loaded->save();

    expect(reloaded($loaded)->getModificationDate())->toBeGreaterThanOrEqual($before);
})->with('elements');
