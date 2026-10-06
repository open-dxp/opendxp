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

it('does not force a load when no option is given', function () {
    $options = Service::prepareGetByIdParams([]);

    expect($options)->toBe(['force' => false]);
});

it('keeps the force option it is given', function (bool $force) {
    $options = Service::prepareGetByIdParams(['force' => $force]);

    expect($options)->toBe(['force' => $force]);
})->with([
    'forced' => [true],
    'not forced' => [false],
]);

it('refuses a force option that is not a boolean', function (mixed $force, string $message) {
    expect(fn () => Service::prepareGetByIdParams(['force' => $force]))
        ->toThrow(InvalidOptionsException::class, $message);
})->with([
    'a string' => [
        'yes',
        'The option "force" with value "yes" is expected to be of type "bool", but is of type "string".',
    ],
    'an integer' => [
        1,
        'The option "force" with value 1 is expected to be of type "bool", but is of type "int".',
    ],
    'null' => [
        null,
        'The option "force" with value null is expected to be of type "bool", but is of type "null".',
    ],
]);

it('refuses an option it does not know', function (array $options) {
    Service::prepareGetByIdParams($options);
})->with([
    'an unknown option alone' => [
        ['unknown' => true],
    ],
    'an unknown option next to force' => [
        [
            'force' => true,
            'unknown' => 'x',
        ],
    ],
])->throws(UndefinedOptionsException::class, 'The option "unknown" does not exist. Defined options are: "force".');
