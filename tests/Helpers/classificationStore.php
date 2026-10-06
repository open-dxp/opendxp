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

use OpenDxp\Model\DataObject\Classificationstore\GroupConfig;
use OpenDxp\Model\DataObject\Classificationstore\KeyConfig;
use OpenDxp\Model\DataObject\Classificationstore\StoreConfig;

/**
 * The suite installs this store from tests/Fixtures/classificationstores before the first test.
 */
const TEST_STORE = 'teststore';

function storeGroup(string $name): GroupConfig
{
    $store = StoreConfig::getByName(TEST_STORE);

    return GroupConfig::getByName($name, $store->getId());
}

function storeKey(string $name): KeyConfig
{
    $store = StoreConfig::getByName(TEST_STORE);

    return KeyConfig::getByName($name, $store->getId());
}
