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

use OpenDxp\Extension\Bundle\AbstractOpenDxpBundle;
use OpenDxp\HttpKernel\Bundle\DependentBundleInterface;
use OpenDxp\HttpKernel\BundleCollection\BundleCollection;
use OpenDxp\HttpKernel\BundleCollection\Item;
use OpenDxp\Tests\Fixtures\Bundle\BundleA;
use OpenDxp\Tests\Fixtures\Bundle\BundleE;
use OpenDxp\Tests\Fixtures\Bundle\BundleF;
use OpenDxp\Tests\Fixtures\Bundle\OpenDxpBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;

it('hands back the bundle it holds', function () {
    expect((new Item(new BundleA()))->getBundle())->toBeInstanceOf(BundleA::class);
});

it('is named after the class of its bundle', function () {
    expect((new Item(new BundleA()))->getBundleIdentifier())->toBe(BundleA::class);
});

it('matches any environment while it names none', function (string $environment) {
    expect((new Item(new BundleA(), 0, []))->matchesEnvironment($environment))->toBeTrue();
})->with(['prod', 'dev', 'test']);

it('matches only the environments it names', function (array $named, string $environment, bool $matches) {
    expect((new Item(new BundleA(), 0, $named))->matchesEnvironment($environment))->toBe($matches);
})->with([
    'the one it names' => [['dev'], 'dev', true],
    'another one' => [['dev'], 'prod', false],
    'the first of two' => [['dev', 'test'], 'dev', true],
    'the second of two' => [['dev', 'test'], 'test', true],
    'none of two' => [['dev', 'test'], 'prod', false],
]);

it('says whether its bundle is an opendxp bundle', function () {
    expect((new Item(new BundleA()))->isOpenDxpBundle())
        ->toBeFalse()
        ->and((new Item(new OpenDxpBundle()))->isOpenDxpBundle())
        ->toBeTrue();
});

it('brings the bundles its own depends on', function () {

    $collection = new BundleCollection();
    $collection->add(new Item(new BundleE()));

    expect($collection->getIdentifiers())->toBe([BundleE::class, BundleF::class]);
});
