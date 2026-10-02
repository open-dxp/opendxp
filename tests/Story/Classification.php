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


namespace OpenDxp\Tests\Story;

use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Classificationstore;
use Zenstruck\Foundry\Story;

/**
 * The classification store the test classes carry, with one group and two text keys in it.
 */
final class Classification extends Story
{
    public function build(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');

        if (!$store instanceof Classificationstore\StoreConfig) {
            $store = new Classificationstore\StoreConfig();
            $store->setName('teststore');
            $store->save();
        }

        $this->addState('store', $store);
        $this->addState('group', self::group($store, 'group1'));
        $this->addState('first', self::key($store, 'field1'));
        $this->addState('second', self::key($store, 'field2'));

        self::addKeyToGroup(self::get('first'), self::get('group'), 1);
        self::addKeyToGroup(self::get('second'), self::get('group'), 2);
    }

    private static function group(Classificationstore\StoreConfig $store, string $name): Classificationstore\GroupConfig
    {
        $group = Classificationstore\GroupConfig::getByName($name, $store->getId());

        if ($group instanceof Classificationstore\GroupConfig) {
            return $group;
        }

        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($store->getId());
        $group->setName($name);
        $group->save();

        return $group;
    }

    private static function key(Classificationstore\StoreConfig $store, string $name): Classificationstore\KeyConfig
    {
        $key = Classificationstore\KeyConfig::getByName($name, $store->getId());

        if ($key instanceof Classificationstore\KeyConfig) {
            return $key;
        }

        $key = new Classificationstore\KeyConfig();
        $key->setStoreId($store->getId());
        $key->setName($name);
        $key->setType('input');
        $key->setDefinition(json_encode(new ClassDefinition\Data\Input()));
        $key->setEnabled(true);
        $key->save();

        return $key;
    }

    private static function addKeyToGroup(
        Classificationstore\KeyConfig $key,
        Classificationstore\GroupConfig $group,
        int $position,
    ): void {
        if (Classificationstore\KeyGroupRelation::getByGroupAndKeyId($group->getId(), $key->getId())) {
            return;
        }

        $relation = new Classificationstore\KeyGroupRelation();
        $relation->setGroupId($group->getId());
        $relation->setKeyId($key->getId());
        $relation->setSorter($position);
        $relation->save();
    }
}
