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

namespace OpenDxp\Model\DataObject\Traits;

use OpenDxp;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\ClassDefinition\Data;

/**
 * @internal
 */
trait FieldDefinitionEnrichmentModelTrait
{
    /**
     * @var array<string, Data>|null
     */
    protected ?array $fieldDefinitionsCache = null;

    /**
     * @param array<string, Data>|null $fieldDefinitions
     *
     * @return $this
     */
    public function setFieldDefinitions(?array $fieldDefinitions): static
    {
        $this->fieldDefinitionsCache = $fieldDefinitions;

        return $this;
    }

    public function suppressEnrichment(array $context): bool
    {
        if (!OpenDxp::inAdmin()) {
            return true;
        }

        return isset($context['suppressEnrichment']) && $context['suppressEnrichment'];
    }

    /**
     * @return array<string, Data>
     */
    protected function getEnrichedFieldDefinitions(array $context = []): array
    {
        $enrichedFieldDefinitions = [];
        if (is_array($this->fieldDefinitionsCache)) {
            foreach ($this->fieldDefinitionsCache as $key => $fieldDefinition) {
                $fieldDefinition = $this->doEnrichFieldDefinition($fieldDefinition, $context);
                $enrichedFieldDefinitions[$key] = $fieldDefinition;
            }
        }

        return $enrichedFieldDefinitions;
    }

    /**
     * @return array<string, Data>
     */
    public function getFieldDefinitions(array $context = []): array
    {
        if ($this->suppressEnrichment($context)) {
            return $this->fieldDefinitionsCache ?? [];
        }

        return $this->getEnrichedFieldDefinitions($context);
    }

    /**
     * @return $this
     */
    public function addFieldDefinition(string $key, Data $data): static
    {
        $this->fieldDefinitionsCache[$key] = $data;

        return $this;
    }

    public function getFieldDefinition(string $key, array $context = []): ?Data
    {
        if (!isset($this->fieldDefinitionsCache)) {
            $this->getFieldDefinitions($context);
        }

        if (isset($this->fieldDefinitionsCache)) {
            $fieldDefinition = null;

            if (array_key_exists($key, $this->fieldDefinitionsCache)) {
                $fieldDefinition = $this->fieldDefinitionsCache[$key];
            } elseif (array_key_exists('localizedfields', $this->fieldDefinitionsCache)) {
                $localizedFields = $this->fieldDefinitionsCache['localizedfields'];
                if ($localizedFields instanceof ClassDefinition\Data\Localizedfields) {
                    $fieldDefinition = $localizedFields->getFieldDefinition($key);
                }
            }

            if ($fieldDefinition) {
                if ($this->suppressEnrichment($context)) {
                    return $fieldDefinition;
                }

                return $this->doEnrichFieldDefinition($fieldDefinition, $context);
            }
        }

        return null;
    }

    abstract protected function doEnrichFieldDefinition(Data $fieldDefinition, array $context = []): Data;
}
