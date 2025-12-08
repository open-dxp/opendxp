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

namespace OpenDxp\DataObject\BlockDataMarshaller;

use OpenDxp\Element\MarshallerService;
use OpenDxp\Marshaller\MarshallerInterface;

/**
 * @internal
 */
class Localizedfields implements MarshallerInterface
{
    /**
     * Localizedfields constructor.
     *
     */
    public function __construct(protected MarshallerService $marshallerService)
    {
    }

    public function marshal(mixed $value, array $params = []): mixed
    {
        $object = $params['object'] ?? null;
        /** @var \OpenDxp\Model\DataObject\ClassDefinition\Data\Localizedfields $fieldDefinition */
        $fieldDefinition = $params['fieldDefinition'];
        $childDefs = $fieldDefinition->getFieldDefinitions();
        $result = [];

        if (is_array($value)) {
            foreach ($value as $language => $items) {
                $result[$language] = [];
                foreach ($items as $key => $normalizedData) {
                    $childDef = $childDefs[$key];

                    if ($this->marshallerService->supportsFielddefinition('block', $childDef->getFieldtype())) {
                        $marshaller = $this->marshallerService->buildFieldefinitionMarshaller('block', $childDef->getFieldtype());
                        // TODO format only passed in for BC reasons (localizedfields). remove it as soon as marshal is gone
                        $encodedData = $marshaller->marshal($normalizedData, ['object' => $object, 'fieldDefinition' => $childDef, 'format' => 'block']);
                    } else {
                        $encodedData = $normalizedData;
                    }
                    $result[$language][$key] = $encodedData;
                }
            }

            return $result;
        }

        return null;
    }

    public function unmarshal(mixed $value, array $params = []): mixed
    {
        $object = $params['object'] ?? null;
        /** @var \OpenDxp\Model\DataObject\ClassDefinition\Data\Localizedfields $fieldDefinition */
        $fieldDefinition = $params['fieldDefinition'];
        $childDefs = $fieldDefinition->getFieldDefinitions();
        $result = [];

        if (is_array($value)) {
            foreach ($value as $language => $items) {
                $result[$language] = [];
                foreach ($items as $key => $normalizedData) {
                    if (!isset($childDefs[$key])) {
                        continue;
                    }

                    $childDef = $childDefs[$key];

                    if ($this->marshallerService->supportsFielddefinition('block', $childDef->getFieldtype())) {
                        $marshaller = $this->marshallerService->buildFieldefinitionMarshaller('block', $childDef->getFieldtype());
                        // TODO format only passed in for BC reasons (localizedfields). remove it as soon as marshal is gone
                        $decodedData = $marshaller->unmarshal($normalizedData, ['object' => $object, 'fieldDefinition' => $childDef, 'format' => 'block']);
                    } else {
                        $decodedData = $normalizedData;
                    }
                    $result[$language][$key] = $decodedData;
                }
            }

            return $result;
        }

        return null;
    }
}
