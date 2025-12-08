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

use OpenDxp\Model\DataObject\ClassDefinition;

final class Key
{
    public function __construct(protected Group $group, protected KeyConfig $configuration)
    {
    }

    public function getConfiguration(): KeyConfig
    {
        return $this->configuration;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function getValue(
        ?string $language = 'default',
        bool $ignoreFallbackLanguage = false,
        bool $ignoreDefaultLanguage = false
    ): mixed {
        $classificationstore = $this->group->getClassificationStore();

        return $classificationstore->getLocalizedKeyValue(
            $this->group->getConfiguration()->getId(),
            $this->configuration->getId(),
            $language,
            $ignoreFallbackLanguage,
            $ignoreDefaultLanguage
        );
    }

    public function getFieldDefinition(): ClassDefinition\Data
    {
        return Service::getFieldDefinitionFromKeyConfig($this->configuration);
    }
}
