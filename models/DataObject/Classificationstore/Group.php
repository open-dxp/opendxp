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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Model\DataObject\Classificationstore;

use OpenDxp\Model\DataObject\Classificationstore;

final class Group
{
    public function __construct(protected Classificationstore $classificationStore, protected GroupConfig $configuration)
    {
    }

    public function getConfiguration(): GroupConfig
    {
        return $this->configuration;
    }

    public function getClassificationStore(): Classificationstore
    {
        return $this->classificationStore;
    }

    /**
     * @return Key[]
     */
    public function getKeys(): array
    {
        return $this->getKeysByKeyGroupRelations(
            ...$this->getKeyGroupRelations()
        );
    }

    /**
     * @return KeyGroupRelation[]
     */
    protected function getKeyGroupRelations(): array
    {
        return $this->getKeyGroupRelationListing()
            ->setCondition('groupId = ' . $this->configuration->getId())
            ->setOrderKey([
                'sorter',
                'keyId',
            ])
            ->load();
    }

    protected function getKeyGroupRelationListing(): KeyGroupRelation\Listing
    {
        return new KeyGroupRelation\Listing();
    }

    /**
     *
     * @return Key[]
     */
    protected function getKeysByKeyGroupRelations(KeyGroupRelation ...$keyGroupRelations): array
    {
        return array_map([$this, 'getKeyByKeyGroupRelation'], $keyGroupRelations);
    }

    protected function getKeyByKeyGroupRelation(KeyGroupRelation $keyGroupRelation): Key
    {
        $keyConfig = $this->getKeyConfigById($keyGroupRelation->getKeyId());

        return new Key($this, $keyConfig);
    }

    public function getKeyConfigById(int $id): KeyConfig
    {
        return KeyConfig::getById($id);
    }
}
