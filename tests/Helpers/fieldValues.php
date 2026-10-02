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


use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\AssetVideoFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\QuantityValueUnitFactory;
use OpenDxp\Test\Factory\UserFactory;

/**
 * The objects a relation field can point at, oldest first. A dataset is built before the test runs,
 * so it reads them back out of the database instead of holding them.
 */
function someObjects(int $count): array
{
    $listing = new DataObject\Listing();
    $listing->setCondition("`type` = 'object'");
    $listing->setOrderKey('id');
    $listing->setLimit($count);

    return $listing->load();
}

function anImage(string $filename): Asset
{
    return Asset::getByPath('/' . $filename) ?? AssetImageFactory::createOne(['key' => $filename]);
}

function aVideo(string $filename = 'video.mp4'): Asset
{
    return Asset::getByPath('/' . $filename) ?? AssetVideoFactory::createOne(['key' => $filename]);
}

function aPage(string $key): Document
{
    return Document::getByPath('/' . $key) ?? DocumentPageFactory::createOne(['key' => $key]);
}

function aUnit(string $abbreviation): DataObject\QuantityValue\Unit
{
    return DataObject\QuantityValue\Unit::getByAbbreviation($abbreviation)
        ?? QuantityValueUnitFactory::createOne(['abbreviation' => $abbreviation]);
}

function aUser(string $name): User
{
    return User::getByName($name) ?? UserFactory::createOne(['name' => $name]);
}

function geoPolygon(): array
{
    return [
        new DataObject\Data\GeoCoordinates(-33.464671118242684, 150.54428100585938),
        new DataObject\Data\GeoCoordinates(-33.913733814316245, 150.73654174804688),
        new DataObject\Data\GeoCoordinates(-33.9946115848146, 151.2542724609375),
    ];
}

/**
 * Two hotspots on an image. Inside a gallery both carry the name of their position, on a single image
 * they are told apart by a number of their own.
 */
function hotspots(?int $position = null, int $seed = 0): array
{
    $offset = $position ?? 0;

    return [
        [
            'name' => sprintf('hotspot_%d_%d', $position ?? 1, $seed),
            'width' => 10 + $offset, 'height' => 20 + $offset, 'top' => 30 + $offset, 'left' => 40 + $offset,
        ],
        [
            'name' => sprintf('hotspot_%d_%d', $position ?? 2, $seed),
            'width' => 10 + $offset, 'height' => 50 + $offset, 'top' => 20 + $offset, 'left' => 40 + $offset,
        ],
    ];
}

function aLink(Document $target): DataObject\Data\Link
{
    $link = new DataObject\Data\Link();
    $link->setPath((string) $target);

    return $link;
}

function aVideoField(): DataObject\Data\Video
{
    $video = new DataObject\Data\Video();
    $video->setType('asset');
    $video->setData(aVideo());
    $video->setPoster(anImage('poster.jpg'));
    $video->setTitle('title1');
    $video->setDescription('description1');

    return $video;
}

function structuredTable(): DataObject\Data\StructuredTable
{
    $table = new DataObject\Data\StructuredTable();
    $table->setData([
        'row1' => ['col1' => 2, 'col2' => 'text_a_1'],
        'row2' => ['col1' => 3, 'col2' => 'text_b_1'],
        'row3' => ['col1' => 4, 'col2' => 'text_c_1'],
    ]);

    return $table;
}
