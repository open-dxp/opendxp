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

use OpenDxp\Db;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Element\AbstractElement;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use OpenDxp\Model\Element\Tag;
use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\TestObjectFactory;

function elementWithPathLength(int $length): Concrete
{
    $object = TestObjectFactory::createOne();
    $object->setKey(str_repeat('a', $length - mb_strlen($object->getRealPath(), 'UTF-8')));

    return $object;
}

function validatePathLength(AbstractElement $element): void
{
    (new ReflectionMethod($element, 'validatePathLength'))->invoke($element);
}

function allowPath(string $type, int $ownerId, string $path, ?int $elementId = null): int
{
    return workspace($type, $ownerId, $path, $elementId, list: 1);
}

function forbidPath(string $type, int $ownerId, string $path, ?int $elementId = null): int
{
    return workspace($type, $ownerId, $path, $elementId, list: 0);
}

function workspace(string $type, int $ownerId, string $path, ?int $elementId, int $list): int
{
    $elementId ??= match ($type) {
        'object' => TestObjectFactory::createOne()->getId(),
        'document' => DocumentPageFactory::createOne()->getId(),
        'asset' => AssetFolderFactory::createOne()->getId(),
    };

    Db::get()->insert(sprintf('users_workspaces_%s', $type), [
        'userId' => $ownerId,
        'cpath'  => $path,
        'cid'    => $elementId,
        'list'   => $list,
    ]);

    return $elementId;
}

function tagElement(Tag $tag, ElementInterface $element): void
{
    Tag::addTagToElement(Service::getElementType($element), $element->getId(), $tag);
}
