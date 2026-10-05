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

use OpenDxp\Model\DataObject\Data\BlockElement;
use OpenDxp\Model\DataObject\Data\ElementMetadata;
use OpenDxp\Model\DataObject\Data\Hotspotimage;
use OpenDxp\Model\DataObject\Data\Link;
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Model\DataObject\UnittestBlock;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\BlockObjectFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

/**
 * A second request reads the object from the cache before it saves it again.
 */
function savedAgainFromTheCache(UnittestBlock $object): UnittestBlock
{
    fromTheCache($object)->save();

    return UnittestBlock::getById($object->getId(), ['force' => true]);
}

function blockWithReferences(string $prefix): array
{
    $page = DocumentPageFactory::createOne();
    $image = AssetImageFactory::createOne();

    $link = new Link();
    $link->setPath($page->getFullPath());

    $hotspots = [
        ['name' => 'hotspot1', 'width' => 10, 'height' => 20, 'top' => 30, 'left' => 40],
        ['name' => 'hotspot2', 'width' => 10, 'height' => 50, 'top' => 20, 'left' => 40],
    ];

    return [
        'page' => $page,
        'image' => $image,
        'data' => [
            $prefix . 'input' => new BlockElement($prefix . 'input', 'input', 'test-input'),
            $prefix . 'link' => new BlockElement($prefix . 'link', 'input', $link),
            $prefix . 'hotspotimage' => new BlockElement(
                $prefix . 'hotspotimage',
                'hotspotimage',
                new Hotspotimage($image, $hotspots),
            ),
        ],
    ];
}

it('keeps the references of a block through a save that came out of the cache', function () {

    ['page' => $page, 'image' => $image, 'data' => $data] = blockWithReferences('block');

    $object = BlockObjectFactory::createOne(['testblock' => [$data]]);
    $block = savedAgainFromTheCache($object)->getTestblock()[0];

    expect($block['blocklink']->getData()->getElement()->getId())
        ->toBe($page->getId())
        ->and($block['blockhotspotimage']->getData()->getImage()->getId())
        ->toBe($image->getId());
});

it('keeps the references of a localized block through a save that came out of the cache', function () {

    ['page' => $page, 'image' => $image, 'data' => $data] = blockWithReferences('lblock');

    $object = BlockObjectFactory::new()->unsaved()->create();
    $object->setLtestblock([$data], 'de');
    $object->save();

    $block = savedAgainFromTheCache($object)->getLtestblock('de')[0];

    expect($block['lblocklink']->getData()->getElement()->getId())
        ->toBe($page->getId())
        ->and($block['lblockhotspotimage']->getData()->getImage()->getId())
        ->toBe($image->getId());
});

it('hands back the relation a block holds now, not the one it held when another object linked it', function () {

    $first = UnittestFactory::createOne();

    $source = BlockObjectFactory::new()->unsaved()->create();
    $source->setLtestblock([[
        'lblockadvancedRelations' => new BlockElement(
            'lblockadvancedRelations',
            'advancedManyToManyRelation',
            [new ElementMetadata('lblockadvancedRelations', [], $first)],
        ),
    ]], 'de');
    $source->save();

    $pointingAtTheSource = UnittestFactory::createOne(['href' => $source]);

    $second = UnittestFactory::createOne();
    $source->getLtestblock('de')[0]['lblockadvancedRelations']
        ->setData([new ElementMetadata('lblockadvancedRelations', [], $second)]);
    $source->save();

    $throughTheOtherObject = Unittest::getById($pointingAtTheSource->getId(), ['force' => true])
        ->getHref()
        ->getLtestblock('de')[0]['lblockadvancedRelations']
        ->getData();

    expect($throughTheOtherObject[0]->getElement()->getId())->toBe($second->getId());
});
