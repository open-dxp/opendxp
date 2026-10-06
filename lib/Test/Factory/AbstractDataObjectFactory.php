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

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\DataObject\Classificationstore\GroupConfig;
use OpenDxp\Model\DataObject\Classificationstore\KeyConfig;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Fieldcollection;
use OpenDxp\Model\DataObject\Fieldcollection\Data\AbstractData as FieldcollectionItem;
use OpenDxp\Model\DataObject\Objectbrick\Data\AbstractData;
use RuntimeException;

/**
 * @template T of Concrete
 *
 * @extends AbstractElementFactory<T>
 */
abstract class AbstractDataObjectFactory extends AbstractElementFactory
{
    public function unpublished(): static
    {
        return $this->with(['published' => false]);
    }

    /**
     * @param array<string, mixed> $byLanguage
     */
    public function withLocalizedValues(string $field, array $byLanguage): static
    {
        return $this->afterInstantiate(
            static function (Concrete $object) use ($field, $byLanguage): void {
                foreach ($byLanguage as $language => $value) {
                    $object->set($field, $value, $language);
                }
            },
        );
    }

    public function withFieldcollection(string $field, FieldcollectionItem ...$items): static
    {
        return $this->with([$field => new Fieldcollection($items, $field)]);
    }

    /**
     * @param class-string<AbstractData> $brick
     * @param array<string, mixed> $values
     */
    public function withObjectbrick(string $field, string $brick, array $values): static
    {
        return $this->afterInstantiate(
            static function (Concrete $object) use ($field, $brick, $values): void {
                $data = new $brick($object);

                foreach ($values as $name => $value) {
                    $data->set($name, $value);
                }

                $object
                    ->get($field)
                    ->set(
                        $data->getType(),
                        $data,
                    );
            },
        );
    }

    /**
     * @param class-string<AbstractData> $brick
     * @param array<string, array<string, mixed>> $valuesByLanguage
     */
    public function withLocalizedObjectbrick(string $field, string $brick, array $valuesByLanguage): static
    {
        return $this->afterInstantiate(
            static function (Concrete $object) use ($field, $brick, $valuesByLanguage): void {
                $data = new $brick($object);

                foreach ($valuesByLanguage as $language => $values) {
                    foreach ($values as $name => $value) {
                        $data->set($name, $value, $language);
                    }
                }

                $object
                    ->get($field)
                    ->set(
                        $data->getType(),
                        $data,
                    );
            },
        );
    }

    /**
     * @param array<string, mixed> $byKeyName
     */
    public function withClassificationValues(string $field, GroupConfig $group, array $byKeyName): static
    {
        return $this->afterInstantiate(
            static function (Concrete $object) use ($field, $group, $byKeyName): void {
                $store = $object->get($field);

                foreach ($byKeyName as $name => $value) {
                    $key = self::keyOf($group, $name);

                    $store->setLocalizedKeyValue(
                        $group->getId(),
                        $key->getId(),
                        $value,
                    );
                }
            },
        );
    }

    /**
     * @param array<string, mixed> $byLanguage
     */
    public function withLocalizedClassificationValues(
        string $field,
        GroupConfig $group,
        string $keyName,
        array $byLanguage,
    ): static {
        return $this->afterInstantiate(
            static function (Concrete $object) use ($field, $group, $keyName, $byLanguage): void {
                $store = $object->get($field);
                $key = self::keyOf($group, $keyName);

                foreach ($byLanguage as $language => $value) {
                    $store->setLocalizedKeyValue(
                        $group->getId(),
                        $key->getId(),
                        $value,
                        $language,
                    );
                }
            },
        );
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'key'       => self::faker()->unique()->slug(),
            'published' => true,
        ];
    }

    private static function keyOf(GroupConfig $group, string $name): KeyConfig
    {
        return KeyConfig::getByName($name, $group->getStoreId())
            ?? throw new RuntimeException(sprintf('The classification store has no key %s.', $name));
    }
}
