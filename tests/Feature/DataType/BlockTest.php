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

namespace OpenDxp\Tests\Feature\DataType;

use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\Data\BlockElement;
use OpenDxp\Model\DataObject\Data\ElementMetadata;
use OpenDxp\Model\DataObject\Data\Hotspotimage;
use OpenDxp\Model\DataObject\Data\Link;
use OpenDxp\Model\DataObject\UnittestBlock;
use OpenDxp\Model\Document;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\UnittestBlockFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

/**
 * A second request reads the object from the cache before it saves it again.
 */
function savedAgainFromTheCache(UnittestBlock $object): UnittestBlock
{
    cachedCopyOf($object)->save();

    return reloaded($object);
}

/**
 * @return array<string, BlockElement>
 */
function blockReferencing(string $prefix, Document $page, Asset $image): array
{
    $link = new Link();
    $link->setPath($page->getFullPath());

    $hotspots = [
        [
            'name' => 'hotspot1',
            'width' => 10,
            'height' => 20,
            'top' => 30,
            'left' => 40,
        ],
        [
            'name' => 'hotspot2',
            'width' => 10,
            'height' => 50,
            'top' => 20,
            'left' => 40,
        ],
    ];

    return [
        $prefix . 'input' => new BlockElement($prefix . 'input', 'input', 'test-input'),
        $prefix . 'link' => new BlockElement($prefix . 'link', 'input', $link),
        $prefix . 'hotspotimage' => new BlockElement(
            $prefix . 'hotspotimage',
            'hotspotimage',
            new Hotspotimage($image, $hotspots),
        ),
    ];
}

it('keeps the references of a block through a save from the cache', function () {
    $page = DocumentPageFactory::createOne();
    $image = AssetImageFactory::createOne();
    $object = UnittestBlockFactory::createOne([
        'testblock' => [blockReferencing('block', $page, $image)],
    ]);

    $block = savedAgainFromTheCache($object)->getTestblock()[0];

    expect($block['blocklink']->getData()->getElement())
        ->getId()
        ->toBe($page->getId())
        ->and($block['blockhotspotimage']->getData()->getImage())
        ->getId()
        ->toBe($image->getId());
});

it('keeps the references of a localized block through a save from the cache', function () {
    $page = DocumentPageFactory::createOne();
    $image = AssetImageFactory::createOne();
    $object = UnittestBlockFactory::new()
        ->withLocalizedValues(
            'ltestblock',
            ['de' => [blockReferencing('lblock', $page, $image)]],
        )
        ->create();

    $block = savedAgainFromTheCache($object)->getLtestblock('de')[0];

    expect($block['lblocklink']->getData()->getElement())
        ->getId()
        ->toBe($page->getId())
        ->and($block['lblockhotspotimage']->getData()->getImage())
        ->getId()
        ->toBe($image->getId());
});

it('reads the current relation of a block through another object', function () {
    $first = UnittestFactory::createOne();
    $second = UnittestFactory::createOne();
    $relation = new BlockElement(
        'lblockadvancedRelations',
        'advancedManyToManyRelation',
        [new ElementMetadata('lblockadvancedRelations', [], $first)],
    );
    $source = UnittestBlockFactory::new()
        ->withLocalizedValues(
            'ltestblock',
            ['de' => [['lblockadvancedRelations' => $relation]]],
        )
        ->create();
    $pointing = UnittestFactory::createOne(['href' => $source]);

    $source
        ->getLtestblock('de')[0]['lblockadvancedRelations']
        ->setData([new ElementMetadata('lblockadvancedRelations', [], $second)]);
    $source->save();
    $current = reloaded($pointing)->getHref()->getLtestblock('de')[0]['lblockadvancedRelations']->getData();

    expect($current[0]->getElement())->getId()->toBe($second->getId());
});
