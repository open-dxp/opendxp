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

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Document;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

describe('an object listing', function () {
    beforeEach(function () {
        $this->objects = UnittestFactory::createMany(
            3,
            fn (int $index) => ['input' => sprintf('listed_%d', $index)],
        );
        RuntimeCache::clear();
    });

    it('returns every object its condition matches', function () {
        $listing = unittestListing('listed_');

        $loaded = $listing->load();

        expect(elementIds($loaded))->toBe(elementIds($this->objects));
    });

    it('returns the same objects on a second load', function () {
        $first = unittestListing('listed_')->load();

        $second = unittestListing('listed_')->load();

        expect(elementIds($second))->toBe(elementIds($first));
    });

    it('keeps the loaded objects on the listing', function () {
        $listing = unittestListing('listed_');

        $loaded = $listing->load();

        expect($listing->getObjects())->toBe($loaded);
    });
});

it('returns elements of the type it lists', function (
    array $created,
    Asset\Listing|Document\Listing $listing,
    string $type,
) {
    RuntimeCache::clear();

    $loaded = $listing->load();

    expect($loaded)
        ->each
        ->toBeInstanceOf($type)
        ->and(elementIds($loaded))
        ->toContain(...elementIds($created));
})->with([
    'assets' => [
        fn () => AssetImageFactory::createMany(3),
        fn () => new Asset\Listing(),
        Asset::class,
    ],
    'documents' => [
        fn () => DocumentPageFactory::createMany(3),
        fn () => new Document\Listing(),
        Document::class,
    ],
]);
