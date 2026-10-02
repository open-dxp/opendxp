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


namespace OpenDxp\Test;

use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\Classificationstore\GroupConfig;
use OpenDxp\Model\DataObject\Classificationstore\KeyConfig;
use OpenDxp\Model\DataObject\Classificationstore\KeyGroupRelation;
use OpenDxp\Model\DataObject\Classificationstore\StoreConfig;
use RuntimeException;

final class ClassificationStores
{
    /**
     * Classes refer to a classification store by its id, so the store is installed before them.
     */
    public static function install(string $name, string $definition): StoreConfig
    {
        $json = file_get_contents($definition);

        if ($json === false) {
            throw new RuntimeException(sprintf('There is no classification store definition at %s.', $definition));
        }

        $described = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        $store = StoreConfig::getByName($name) ?? new StoreConfig();
        $store->setName($name);
        $store->setDescription($described['description'] ?? '');
        $store->save();

        foreach ($described['groups'] ?? [] as $groupName => $keys) {
            self::installGroup($store, (string) $groupName, $keys);
        }

        return $store;
    }

    /**
     * @param array<string, array<string, mixed>> $keys
     */
    private static function installGroup(StoreConfig $store, string $name, array $keys): void
    {
        $group = GroupConfig::getByName($name, $store->getId()) ?? new GroupConfig();
        $group->setStoreId($store->getId());
        $group->setName($name);
        $group->save();

        $position = 0;

        foreach ($keys as $keyName => $described) {
            $key = self::installKey($store, (string) $keyName, $described);

            if (KeyGroupRelation::getByGroupAndKeyId($group->getId(), $key->getId())) {
                continue;
            }

            $relation = new KeyGroupRelation();
            $relation->setGroupId($group->getId());
            $relation->setKeyId($key->getId());
            $relation->setSorter(++$position);
            $relation->save();
        }
    }

    /**
     * @param array<string, mixed> $described
     */
    private static function installKey(StoreConfig $store, string $name, array $described): KeyConfig
    {
        $key = KeyConfig::getByName($name, $store->getId()) ?? new KeyConfig();
        $key->setStoreId($store->getId());
        $key->setName($name);
        $key->setType($described['type']);
        $key->setDescription($described['description'] ?? '');
        $key->setEnabled(true);
        $key->setDefinition(json_encode(self::fieldDefinition($name, $described['type']), JSON_THROW_ON_ERROR));
        $key->save();

        return $key;
    }

    /**
     * The field definition a key carries. The editor renders the field from it, and the store marshals
     * a value through it, so it has to be the definition of the type the key names.
     */
    private static function fieldDefinition(string $name, string $type): Data
    {
        $class = sprintf('%s\\%s', Data::class, ucfirst($type));

        if (!class_exists($class)) {
            throw new RuntimeException(sprintf('There is no field type called %s.', $type));
        }

        $definition = new $class();
        $definition->setName($name);

        if ($definition instanceof Data\EncryptedField) {
            $definition->setDelegateDatatype('input');
            $definition->setDelegate(new Data\Input());
        }

        return $definition;
    }
}
