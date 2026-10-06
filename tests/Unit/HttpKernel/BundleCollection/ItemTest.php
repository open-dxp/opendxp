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

namespace OpenDxp\Tests\Unit\HttpKernel\BundleCollection;

use OpenDxp\HttpKernel\BundleCollection\Item;
use OpenDxp\Tests\Fixtures\Bundle\FirstBundle;
use OpenDxp\Tests\Fixtures\Bundle\OpenDxpBundle;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

it('is named after the class of its bundle', function () {
    $item = new Item(new FirstBundle());

    expect($item)->getBundleIdentifier()->toBe(FirstBundle::class);
});

it('matches every environment while it names none', function (string $environment) {
    $item = new Item(new FirstBundle());

    $matches = $item->matchesEnvironment($environment);

    expect($matches)->toBeTrue();
})->with([
    'production' => ['prod'],
    'development' => ['dev'],
    'test' => ['test'],
]);

it('matches only the environments it names', function (string $environment, bool $matches) {
    $item = new Item(
        new FirstBundle(),
        environments: [
            'dev',
            'test',
        ],
    );

    $result = $item->matchesEnvironment($environment);

    expect($result)->toBe($matches);
})->with([
    'the first one it names' => ['dev', true],
    'the second one it names' => ['test', true],
    'one it does not name' => ['prod', false],
]);

it('tells whether its bundle is an OpenDXP bundle', function (BundleInterface $bundle, bool $openDxpBundle) {
    $item = new Item($bundle);

    $result = $item->isOpenDxpBundle();

    expect($result)->toBe($openDxpBundle);
})->with([
    'a plain bundle' => [
        fn () => new FirstBundle(),
        false,
    ],
    'an OpenDXP bundle' => [
        fn () => new OpenDxpBundle(),
        true,
    ],
]);
