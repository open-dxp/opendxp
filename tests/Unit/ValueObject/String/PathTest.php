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

namespace OpenDxp\Tests\Unit\ValueObject\String;

use OpenDxp\ValueObject\String\Path;
use ValueError;

it('refuses a path that does not start with a slash', function () {
    new Path('path');
})->throws(ValueError::class, 'Path must start with a slash.');

it('refuses a path with two slashes in a row', function () {
    new Path('/path//path');
})->throws(ValueError::class, 'Path must not contain consecutive slashes.');

it('accepts a path that starts with a slash', function () {
    $path = new Path('/path');

    expect($path)->getValue()->toBe('/path');
});

it('equals another one only when it holds the same path', function (string $other, bool $equal) {
    $path = new Path('/path');

    $result = $path->equals(new Path($other));

    expect($result)->toBe($equal);
})->with([
    'the same path' => ['/path', true],
    'another path' => ['/path2', false],
]);

it('checks the path again when it is unserialized', function () {
    $serialized = serialize(new Path('/mypath'));
    $tampered = str_replace('/mypath', '!mypath', $serialized);

    expect(fn () => unserialize($tampered))
        ->toThrow(ValueError::class, 'Path must start with a slash.');
});
