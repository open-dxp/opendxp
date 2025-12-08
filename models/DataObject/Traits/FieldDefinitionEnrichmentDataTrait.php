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

use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\ClassDefinition\Data;

/**
 * @internal
 */
trait FieldDefinitionEnrichmentDataTrait
{
    use FieldDefinitionEnrichmentModelTrait;

    /**
     * @param array<string, Data> $fields
     *
     * @return array<string, Data>
     */
    protected function doGetFieldDefinitions(mixed $def = null, array $fields = []): array
    {
        if ($def === null) {
            $def = $this->getChildren();
        }

        if (is_array($def)) {
            foreach ($def as $child) {
                $fields = [...$fields, ...$this->doGetFieldDefinitions($child, $fields)];
            }
        }

        if ($def instanceof ClassDefinition\Layout && $def->hasChildren()) {
            foreach ($def->getChildren() as $child) {
                $fields = [...$fields, ...$this->doGetFieldDefinitions($child, $fields)];
            }
        }

        if ($def instanceof ClassDefinition\Data) {
            $existing = $fields[$def->getName()] ?? false;
            if ($existing && method_exists($existing, 'addReferencedField')) {
                // this is especially for localized fields which get aggregated here into one field definition
                // in the case that there are more than one localized fields in the class definition
                // see also opendxp.object.edit.addToDataFields();
                $existing->addReferencedField($def);
            } else {
                $fields[$def->getName()] = $def;
            }
        }

        return $fields;
    }

    /**
     * @return array<string, Data>
     */
    public function getFieldDefinitions(array $context = []): array
    {
        if (null === $this->fieldDefinitionsCache) {
            $definitions = $this->doGetFieldDefinitions();
            foreach ($this->getReferencedFields() as $rf) {
                if ($rf instanceof ClassDefinition\Data\Localizedfields) {
                    $definitions = [...$definitions, ...$this->doGetFieldDefinitions($rf->getChildren())];
                }
            }

            $this->fieldDefinitionsCache = $definitions;
        }

        if ($this->suppressEnrichment($context)) {
            return $this->fieldDefinitionsCache;
        }

        return $this->getEnrichedFieldDefinitions();
    }
}
