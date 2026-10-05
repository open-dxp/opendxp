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


namespace OpenDxp\Tests\Feature\Asset;

use Doctrine\DBAL\Query\QueryBuilder;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Element\Tag;
use OpenDxp\Test\Factory\AssetDocumentFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\AssetVideoFactory;
use OpenDxp\Test\Factory\TagFactory;

// An installation starts with the asset root folder, and a listing counts it like any other asset.
const ASSETS = 6;

function onlyTagged(Asset\Listing $listing, Tag ...$tags): Asset\Listing
{
    $listing->getDao()->onCreateQueryBuilder(static function (QueryBuilder $query) use ($tags): void {
        $expression = $query->expr();
        $ids = array_map(static fn (Tag $tag) => $expression->literal($tag->getId()), $tags);

        $query
            ->innerJoin('assets', 'tags_assignment', 'ta', $expression->and(
                $expression->in('ta.tagid', $ids),
                $expression->eq('ta.ctype', $expression->literal('asset')),
                $expression->eq('ta.cid', 'assets.id'),
            ))
            ->groupBy('assets.id');
    });

    return $listing;
}

beforeEach(function () {
    $this->tagA = TagFactory::createOne(['name' => 'A']);
    $this->tagB = TagFactory::createOne(['name' => 'B']);

    $first = AssetImageFactory::createOne();
    tagElement($this->tagA, $first);
    tagElement($this->tagB, $first);

    AssetImageFactory::createOne();

    $third = AssetImageFactory::createOne();
    tagElement($this->tagB, $third);

    $document = AssetDocumentFactory::createOne();
    tagElement($this->tagA, $document);
    tagElement($this->tagB, $document);

    $video = AssetVideoFactory::createOne();
    tagElement($this->tagB, $video);
});

it('counts every asset there is', function () {
    expect((new Asset\Listing())->getTotalCount())->toBe(ASSETS);
});

it('counts only as many as the limit allows', function () {

    $listing = new Asset\Listing();
    $listing->setLimit(3);
    $listing->setOffset(1);

    expect($listing->getCount())->toBe(3);
});

it('counts what is left behind the offset', function () {

    $listing = new Asset\Listing();
    $listing->setLimit(10);
    $listing->setOffset(1);

    expect($listing->getCount())->toBe(ASSETS - 1);
});

it('counts the same once the listing was loaded', function () {

    $listing = new Asset\Listing();
    $listing->setLimit(10);
    $listing->setOffset(1);
    $listing->load();

    expect($listing->getCount())
        ->toBe(ASSETS - 1)
        ->and($listing->getTotalCount())
        ->toBe(ASSETS);
});

it('counts the rows of a grouped query, not its groups', function () {

    $listing = onlyTagged(new Asset\Listing(), $this->tagA, $this->tagB);

    expect($listing->getTotalCount())->toBe(4);

    $listing->load();

    expect($listing->getCount())->toBe(4);
});

it('counts every row of a grouped query even under a limit', function () {

    $listing = onlyTagged(new Asset\Listing(), $this->tagA, $this->tagB);
    $listing->setLimit(3);

    expect($listing->getTotalCount())->toBe(4);

    $listing->load();

    expect($listing->getCount())->toBe(3);
});
