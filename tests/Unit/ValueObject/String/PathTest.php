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

it('answers with the path it was given', function () {
    expect((new Path('/path'))->getValue())->toBe('/path');
});

it('equals another one holding the same path', function () {

    $path = new Path('/path');

    expect($path->equals(new Path('/path')))
        ->toBeTrue()
        ->and($path->equals(new Path('/path2')))
        ->toBeFalse();
});

it('checks the path again when it comes back from a serialized form', function () {

    $serialized = str_replace('/mypath', '!mypath', serialize(new Path('/mypath')));

    unserialize($serialized);
})->throws(ValueError::class, 'Path must start with a slash.');
