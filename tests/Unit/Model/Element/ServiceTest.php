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

namespace OpenDxp\Tests\Unit\Model\Element;

use OpenDxp\Model\Element\Service;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

it('asks for no force when the caller names nothing', function () {
    expect(Service::prepareGetByIdParams([]))->toBe(['force' => false]);
});

it('keeps the force the caller names', function (bool $force) {
    expect(Service::prepareGetByIdParams(['force' => $force]))->toBe(['force' => $force]);
})->with([true, false]);

it('refuses a force that is not a boolean', function (mixed $force) {
    Service::prepareGetByIdParams(['force' => $force]);
})->with(['yes', 1, null])->throws(InvalidOptionsException::class);

it('refuses an option it does not know', function (array $params) {
    Service::prepareGetByIdParams($params);
})->with([
    [['unknown' => true]],
    [['force' => true, 'unknown' => 'x']],
])->throws(UndefinedOptionsException::class);

it('answers every call on its own', function () {

    $plain = Service::prepareGetByIdParams([]);
    $forced = Service::prepareGetByIdParams(['force' => true]);

    expect($plain)
        ->toBe(['force' => false])
        ->and($forced)
        ->toBe(['force' => true])
        ->and(Service::prepareGetByIdParams([]))
        ->toBe(['force' => false]);
});
