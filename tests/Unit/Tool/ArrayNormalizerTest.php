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

beforeEach(fn () => $this->values = [
    'a' => 'foo',
    'b' => 'bar',
    'c' => 'baz',
]);

it('leaves an array alone while it has no normalizer', function () {
    $normalized = (new ArrayNormalizer())->normalize($this->values);

    expect($normalized)->toBe($this->values);
});

it('runs a normalizer only on the keys it was added for', function () {
    $normalizer = new ArrayNormalizer();
    $normalizer->addNormalizer(
        [
            'a',
            'b',
        ],
        fn (string $value) => 'first:' . $value,
    );

    $normalized = $normalizer->normalize($this->values);

    expect($normalized)->toBe([
        'a' => 'first:foo',
        'b' => 'first:bar',
        'c' => 'baz',
    ]);
});

it('runs each normalizer on its own key', function () {
    $normalizer = new ArrayNormalizer();
    $normalizer->addNormalizer('a', fn (string $value) => 'first:' . $value);
    $normalizer->addNormalizer('b', fn (string $value) => 'second:' . $value);

    $normalized = $normalizer->normalize($this->values);

    expect($normalized)->toBe([
        'a' => 'first:foo',
        'b' => 'second:bar',
        'c' => 'baz',
    ]);
});

it('passes the key and the whole array to a normalizer', function () {
    $calls = [];
    $normalizer = new ArrayNormalizer();
    $normalizer->addNormalizer('a', function (string $value, string $key, array $values) use (&$calls) {
        $calls[] = [$key, $values];

        return $value;
    });

    $normalizer->normalize($this->values);

    expect($calls)->toBe([
        ['a', $this->values],
    ]);
});
