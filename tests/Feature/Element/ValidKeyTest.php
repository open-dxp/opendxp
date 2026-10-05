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

use OpenDxp\Model\Element\Service;

const GREATEST_KEY_LENGTH = 255;

it('cuts a key to the greatest allowed length', function (string $character) {

    $key = Service::getValidKey(str_repeat($character, 300), 'object');

    expect(mb_strlen($key, 'UTF-8'))
        ->toBe(GREATEST_KEY_LENGTH)
        ->and($key)
        ->toBe(str_repeat($character, GREATEST_KEY_LENGTH));
})->with([
    'ascii' => ['a'],
    'latin with an accent' => ['é'],
    'latin extended' => ['ą'],
    'cyrillic' => ['А'],
]);

it('cuts between characters, not inside one', function () {

    $mixed = 'a' . str_repeat('é', 100) . str_repeat('€', 100) . str_repeat('a', 100);
    $key = Service::getValidKey($mixed, 'object');

    expect(mb_strlen($key, 'UTF-8'))
        ->toBe(GREATEST_KEY_LENGTH)
        ->and($key)
        ->toStartWith('a')
        ->toContain('é')
        ->toContain('€');
});

it('leaves an ascii key as it is', function () {

    $ascii = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    expect(Service::getValidKey($ascii, 'object'))->toBe($ascii);
});

it('replaces a character outside the basic planes with a hyphen', function () {
    expect(Service::getValidKey('abc📦def', 'object'))->toBe('abc-def');
});

it('replaces a slash with a hyphen', function () {
    expect(Service::getValidKey('my/key/name', 'object'))->toBe('my-key-name');
});

it('drops a control character', function (string $control) {
    expect(Service::getValidKey('my' . $control . 'key', 'object'))->toBe('mykey');
})->with([
    'a null byte' => ["\x00"],
    'a tab' => ["\t"],
    'a newline' => ["\n"],
]);

it('trims the whitespace around a key', function () {
    expect(Service::getValidKey('    mykey   ', 'object'))->toBe('mykey');
});

it('keeps the whitespace inside a key', function () {
    expect(Service::getValidKey('my    key   name', 'object'))->toBe('my    key   name');
});

it('drops the whitespace the cut exposes', function () {

    $key = Service::getValidKey(str_repeat('a', 250) . '     TRUNCATETHIS', 'object');

    expect($key)->toBe(str_repeat('a', 250));
});
