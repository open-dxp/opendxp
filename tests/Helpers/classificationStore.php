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

use OpenDxp\Model\DataObject\Classificationstore;
use OpenDxp\Model\DataObject\Classificationstore\GroupConfig;
use OpenDxp\Model\DataObject\Classificationstore\KeyConfig;
use OpenDxp\Model\DataObject\Classificationstore\StoreConfig;

/**
 * All tests use this store. It is installed from tests/Fixtures/classificationstores before the first test.
 */
function theStore(): StoreConfig
{
    return StoreConfig::getByName('teststore');
}

function storeGroup(string $name): GroupConfig
{
    return GroupConfig::getByName($name, theStore()->getId());
}

function storeKey(string $name): KeyConfig
{
    return KeyConfig::getByName($name, theStore()->getId());
}

function keysOfGroup(string $name): array
{
    $listing = new Classificationstore\KeyGroupRelation\Listing();
    $listing->setCondition('groupId = ' . storeGroup($name)->getId());

    return $listing->load();
}
