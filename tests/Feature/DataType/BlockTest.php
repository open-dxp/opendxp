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

use Exception;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\Data\BlockElement;
use OpenDxp\Model\DataObject\Data\ElementMetadata;
use OpenDxp\Model\DataObject\Data\Hotspotimage;
use OpenDxp\Model\DataObject\Data\Link;
use OpenDxp\Model\DataObject\TestGuide;
use OpenDxp\Model\Document;
use OpenDxp\Test\Elements;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\TestCategoryFactory;
use OpenDxp\Test\Factory\TestGuideFactory;
use OpenDxp\Tests\Factory\TestFieldTypesFactory;

/**
 * A second request reads the object from the cache before it saves it again.
 */
function savedAgainFromTheCache(TestGuide $object): TestGuide
{
    cachedCopyOf($object)->save();

    return Elements::refresh($object);
}

/**
 * @return array<string, BlockElement>
 */
function blockReferencing(Document $page, Asset $image, string $textField, string $linkField, string $imageField): array
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
        $textField => new BlockElement($textField, 'input', 'test-input'),
        $linkField => new BlockElement($linkField, 'input', $link),
        $imageField => new BlockElement(
            $imageField,
            'hotspotimage',
            new Hotspotimage($image, $hotspots),
        ),
    ];
}

it('keeps the references of a block through a save from the cache', function () {
    $page = DocumentPageFactory::createOne();
    $image = AssetImageFactory::createOne();
    $object = TestGuideFactory::createOne([
        'steps' => [blockReferencing($page, $image, 'title', 'link', 'image')],
    ]);

    $block = savedAgainFromTheCache($object)->getSteps()[0];

    expect($block['link']->getData()->getElement())
        ->getId()
        ->toBe($page->getId())
        ->and($block['image']->getData()->getImage())
        ->getId()
        ->toBe($image->getId());
});

it('keeps the references of a localized block through a save from the cache', function () {
    $page = DocumentPageFactory::createOne();
    $image = AssetImageFactory::createOne();
    $object = TestGuideFactory::new()
        ->withLocalizedValues(
            'tips',
            ['de' => [blockReferencing($page, $image, 'tipText', 'tipLink', 'tipImage')]],
        )
        ->create();

    $block = savedAgainFromTheCache($object)->getTips('de')[0];

    expect($block['tipLink']->getData()->getElement())
        ->getId()
        ->toBe($page->getId())
        ->and($block['tipImage']->getData()->getImage())
        ->getId()
        ->toBe($image->getId());
});

it('reads the current relation of a block through another object', function () {
    $first = TestCategoryFactory::createOne();
    $second = TestCategoryFactory::createOne();
    $relation = new BlockElement(
        'tipTools',
        'advancedManyToManyRelation',
        [new ElementMetadata('tipTools', [], $first)],
    );
    $source = TestGuideFactory::new()
        ->withLocalizedValues(
            'tips',
            ['de' => [['tipTools' => $relation]]],
        )
        ->create();
    $pointing = TestFieldTypesFactory::createOne(['href' => $source]);

    $source
        ->getTips('de')[0]['tipTools']
        ->setData([new ElementMetadata('tipTools', [], $second)]);
    $source->save();
    $current = Elements::refresh($pointing)->getHref()->getTips('de')[0]['tipTools']->getData();

    expect($current[0]->getElement())->getId()->toBe($second->getId());
});

it('rejects a block item with an original index outside the saved block', function () {
    $object = TestGuideFactory::new()
        ->withSections('first', 'second')
        ->create();
    $submitted = [
        [
            'oIndex' => 2,
            'data' => ['localizedfields' => ['de' => ['heading' => 'third']]],
        ],
    ];
    $block = $object->getClass()->getFieldDefinition('sections');

    expect(fn () => $block->getDataFromEditmode($submitted, $object))
        ->toThrow(Exception::class, 'item with orginal index 2 not found');
});
