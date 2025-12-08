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

use Exception;
use OpenDxp;
use OpenDxp\Model\DataObject;

/**
 * @internal
 */
class Service
{
    /**
     * @var array Used for storing definitions
     */
    protected static array $definitionsCache = [];

    /**
     * Clears the cache for the definitions
     */
    public static function clearDefinitionsCache(): void
    {
        self::$definitionsCache = [];
    }

    /**
     *
     *
     * @throws Exception
     */
    public static function getFieldDefinitionFromKeyConfig(KeyConfig|KeyGroupRelation $keyConfig): DataObject\ClassDefinition\Data\EncryptedField|DataObject\ClassDefinition\Data|null
    {
        if ($keyConfig instanceof KeyConfig) {
            $cacheId = $keyConfig->getId();
        } elseif ($keyConfig instanceof KeyGroupRelation) {
            $cacheId = $keyConfig->getKeyId();
        } else {
            throw new Exception('$keyConfig should be KeyConfig or KeyGroupRelation');
        }

        if (array_key_exists($cacheId, self::$definitionsCache)) {
            return self::$definitionsCache[$cacheId];
        }

        $definition = $keyConfig->getDefinition();
        $definition = json_decode($definition, true);
        $type = $keyConfig->getType();
        $fd = self::getFieldDefinitionFromJson($definition, $type);
        self::$definitionsCache[$cacheId] = $fd;

        return $fd;
    }

    public static function getFieldDefinitionFromJson(array $definition, string $type): DataObject\ClassDefinition\Data\EncryptedField|DataObject\ClassDefinition\Data|null
    {
        if (!$definition) {
            return null;
        }

        if (!$type) {
            $type = 'input';
        }

        $loader = OpenDxp::getContainer()->get('opendxp.implementation_loader.object.data');

        /** @var DataObject\ClassDefinition\Data $dataDefinition */
        $dataDefinition = $loader->build($type);

        $dataDefinition->setValues($definition);
        $className = $dataDefinition::class;

        $dataDefinition = $className::__set_state((array) $dataDefinition);

        if ($dataDefinition instanceof DataObject\ClassDefinition\Data\EncryptedField) {
            $delegateDefinitionRaw = $dataDefinition->getDelegate();
            $delegateDataType = $dataDefinition->getDelegateDatatype();
            $delegateDefinition = self::getFieldDefinitionFromJson((array) $delegateDefinitionRaw, $delegateDataType);
            $dataDefinition->setDelegate($delegateDefinition);
        }

        return $dataDefinition;
    }
}
