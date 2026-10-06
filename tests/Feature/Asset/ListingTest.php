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

/**
 * The join returns one row for each tag of an asset. The query groups these rows by asset.
 */
function taggedWithAnyOf(Tag ...$tags): Asset\Listing
{
    $listing = new Asset\Listing();
    $listing->getDao()->onCreateQueryBuilder(
        static function (QueryBuilder $query) use ($tags): void {
            $expression = $query->expr();
            $tagIds = array_map(
                static fn (Tag $tag): string => $expression->literal($tag->getId()),
                $tags,
            );
            $joinCondition = $expression->and(
                $expression->in('ta.tagid', $tagIds),
                $expression->eq(
                    'ta.ctype',
                    $expression->literal('asset'),
                ),
                $expression->eq('ta.cid', 'assets.id'),
            );

            $query
                ->innerJoin('assets', 'tags_assignment', 'ta', $joinCondition)
                ->groupBy('assets.id');
        },
    );

    return $listing;
}

beforeEach(function () {
    $image = AssetImageFactory::createOne();
    $secondImage = AssetImageFactory::createOne();
    $document = AssetDocumentFactory::createOne();
    $video = AssetVideoFactory::createOne();
    AssetImageFactory::createOne();

    $this->tagA = TagFactory::new()
        ->assignedTo($image, $document)
        ->create();
    $this->tagB = TagFactory::new()
        ->assignedTo($image, $secondImage, $document, $video)
        ->create();
});

it('counts each asset of a grouped query once', function () {
    $listing = taggedWithAnyOf($this->tagA, $this->tagB);

    $count = $listing->getTotalCount();

    expect($count)->toBe(4);
});

it('loads each asset of a grouped query once', function () {
    $listing = taggedWithAnyOf($this->tagA, $this->tagB);

    $assets = $listing->load();

    expect($assets)->toHaveCount(4);
});

it('counts each asset of a grouped query once beyond its limit', function () {
    $listing = taggedWithAnyOf($this->tagA, $this->tagB);
    $listing->setLimit(3);

    $count = $listing->getTotalCount();

    expect($count)->toBe(4);
});

it('loads no more assets of a grouped query than its limit allows', function () {
    $listing = taggedWithAnyOf($this->tagA, $this->tagB);
    $listing->setLimit(3);

    $assets = $listing->load();

    expect($assets)->toHaveCount(3);
});
