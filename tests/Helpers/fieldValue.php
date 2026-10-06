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
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\QuantityValueUnitFactory;
use OpenDxp\Test\Factory\UserFactory;

/**
 * Two values in the same test may name the same image, page, unit or user. The second one finds what the first
 * one created.
 */
function image(string $filename): Asset
{
    return Asset::getByPath('/' . $filename) ?? AssetImageFactory::createOne(['key' => $filename]);
}

function page(string $key): Document
{
    return Document::getByPath('/' . $key) ?? DocumentPageFactory::createOne(['key' => $key]);
}

function quantityUnit(string $abbreviation): DataObject\QuantityValue\Unit
{
    return DataObject\QuantityValue\Unit::getByAbbreviation($abbreviation)
        ?? QuantityValueUnitFactory::createOne(['abbreviation' => $abbreviation]);
}

function user(string $name): User
{
    return User::getByName($name) ?? UserFactory::createOne(['name' => $name]);
}

function linkTo(Document $target): DataObject\Data\Link
{
    $link = new DataObject\Data\Link();
    $link->setPath($target->getFullPath());

    return $link;
}

function video(Asset $data): DataObject\Data\Video
{
    $video = new DataObject\Data\Video();
    $video->setType('asset');
    $video->setData($data);
    $video->setPoster(image('poster.jpg'));
    $video->setTitle('title');
    $video->setDescription('description');

    return $video;
}
