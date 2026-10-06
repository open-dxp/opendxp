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

it('cuts a key to 255 characters', function (string $character) {
    $long = str_repeat($character, 300);

    $key = Service::getValidKey($long, 'object');

    expect($key)->toBe(str_repeat($character, 255));
})->with([
    'ascii' => ['a'],
    'latin with an accent' => ['é'],
    'latin extended' => ['ą'],
    'cyrillic' => ['А'],
]);

it('cuts between characters, not inside one', function () {
    $mixed = sprintf(
        'a%s%s%s',
        str_repeat('é', 100),
        str_repeat('€', 100),
        str_repeat('a', 100),
    );
    $cut = sprintf(
        'a%s%s%s',
        str_repeat('é', 100),
        str_repeat('€', 100),
        str_repeat('a', 54),
    );

    $key = Service::getValidKey($mixed, 'object');

    expect($key)->toBe($cut);
});

it('leaves an ascii key as it is', function () {
    $ascii = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    $key = Service::getValidKey($ascii, 'object');

    expect($key)->toBe($ascii);
});

it('replaces a character outside the basic planes with a hyphen', function () {
    $key = Service::getValidKey('abc📦def', 'object');

    expect($key)->toBe('abc-def');
});

it('replaces a slash with a hyphen', function () {
    $key = Service::getValidKey('my/key/name', 'object');

    expect($key)->toBe('my-key-name');
});

it('drops a control character', function (string $control) {
    $key = Service::getValidKey(sprintf('my%skey', $control), 'object');

    expect($key)->toBe('mykey');
})->with([
    'a null byte' => ["\x00"],
    'a tab' => ["\t"],
    'a newline' => ["\n"],
]);

it('trims the whitespace around a key', function () {
    $key = Service::getValidKey('    mykey   ', 'object');

    expect($key)->toBe('mykey');
});

it('keeps the whitespace inside a key', function () {
    $key = Service::getValidKey('my    key   name', 'object');

    expect($key)->toBe('my    key   name');
});

it('drops the whitespace the cut exposes', function () {
    $long = sprintf('%s     TRUNCATETHIS', str_repeat('a', 250));

    $key = Service::getValidKey($long, 'object');

    expect($key)->toBe(str_repeat('a', 250));
});
