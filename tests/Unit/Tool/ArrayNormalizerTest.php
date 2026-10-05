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

namespace OpenDxp\Tests\Unit\Tool;

use OpenDxp\Tool\ArrayNormalizer;

const INPUT = ['a' => 'foo', 'b' => 'bar', 'c' => 'baz', 'd' => 'inga'];

it('leaves an array alone while it knows no normalizer', function () {
    expect((new ArrayNormalizer())->normalize(INPUT))->toBe(INPUT);
});

it('runs a normalizer on the keys it was named for', function () {

    $normalizer = new ArrayNormalizer();
    $normalizer->addNormalizer(['a', 'b'], fn (string $value) => 'first:' . $value);
    $normalizer->addNormalizer('c', fn (string $value) => 'second:' . $value);

    expect($normalizer->normalize(INPUT))->toBe([
        'a' => 'first:foo',
        'b' => 'first:bar',
        'c' => 'second:baz',
        'd' => 'inga',
    ]);
});

it('hands a normalizer the key and the whole array next to the value', function () {

    $seen = [];
    $normalizer = new ArrayNormalizer();
    $normalizer->addNormalizer(['a', 'b'], function ($value, $key, $values) use (&$seen) {
        $seen[] = [$key, $value, $values];

        return $value;
    });

    expect($normalizer->normalize(INPUT))
        ->toBe(INPUT)
        ->and($seen)
        ->toBe([['a', 'foo', INPUT], ['b', 'bar', INPUT]]);
});
