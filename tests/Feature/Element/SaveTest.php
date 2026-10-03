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

use Carbon\Carbon;

it('keeps the note a version was saved with', function (string $element, string $factory) {

    $saved = $factory::createOne();

    $saved->save(['versionNote' => 'a new version of this element']);

    expect($saved->getLatestVersion(null, true)->getNote())->toBe('a new version of this element');
})->with('elements');

it('keeps the user a save was attributed to', function (string $element, string $factory) {

    $saved = $factory::createOne();

    $saved->setUserModification(101);
    $saved->save();

    expect($saved->getUserModification())->toBe(101);
})->with('elements');

it('attributes a save that names no user to nobody', function (string $element, string $factory) {

    $saved = $factory::createOne();
    $saved->setUserModification(101);
    $saved->save();

    $reloaded = $element::getById($saved->getId(), ['force' => true]);
    $reloaded->save();

    expect($reloaded->getUserModification())->toBe(0);
})->with('elements');

it('keeps the modification date a save was given', function (string $element, string $factory) {

    $saved = $factory::createOne();
    $earlier = (new Carbon())->subHour()->getTimestamp();

    $saved->setModificationDate($earlier);
    $saved->save();

    expect($saved->getModificationDate())->toBe($earlier);
})->with('elements');

it('stamps a save that names no modification date with the current time', function (string $element, string $factory) {

    $saved = $factory::createOne();
    $saved->setModificationDate((new Carbon())->subHour()->getTimestamp());
    $saved->save();

    $before = time();
    $reloaded = $element::getById($saved->getId(), ['force' => true]);
    $reloaded->save();

    expect($reloaded->getModificationDate())->toBeGreaterThanOrEqual($before);
})->with('elements');
