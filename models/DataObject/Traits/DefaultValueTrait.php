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

use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition\DefaultValueGeneratorInterface;
use OpenDxp\Model\DataObject\ClassDefinition\Helper\DefaultValueGeneratorResolver;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Exception\InheritanceParentNotFoundException;
use OpenDxp\Model\DataObject\Localizedfield;
use OpenDxp\Model\DataObject\Objectbrick\Data\AbstractData;

/**
 * @internal
 */
trait DefaultValueTrait
{
    public string $defaultValueGenerator = '';

    abstract protected function doGetDefaultValue(Concrete $object, array $context = []): mixed;

    /**
     *
     * @return mixed $data
     */
    protected function handleDefaultValue(mixed $data, ?Concrete $object = null, array $params = []): mixed
    {
        // 1. only for create, not on update. otherwise there is no way to null it out anymore.
        if ($params['isUpdate'] ?? true) {
            return $data;
        }

        // 2. we already have a value, no need to look for a default value.
        if (!$this->isEmpty($data)) {
            return $data;
        }

        $owner = $params['owner'] ?? null;

        // 3. if we have an object and a default value generator, use this to create a default value.
        if ($object instanceof \OpenDxp\Model\DataObject\Concrete && !empty($this->defaultValueGenerator)) {
            $defaultValueGenerator = DefaultValueGeneratorResolver::resolveGenerator($this->defaultValueGenerator);

            if ($defaultValueGenerator instanceof DefaultValueGeneratorInterface) {
                $context = [...$params['context'] ?? [], ...match (true) {
                    $owner instanceof Concrete => [
                        'ownerType' => 'object',
                        'fieldname' => $this->getName(),
                    ],
                    $owner instanceof Localizedfield => [
                        'ownerType' => 'localizedfield',
                        'ownerName' => 'localizedfields',
                        'position' => $params['language'],
                        'fieldname' => $this->getName(),
                    ],
                    $owner instanceof \OpenDxp\Model\DataObject\Fieldcollection\Data\AbstractData => [
                        'ownerType' => 'fieldcollection',
                        'ownerName' => $owner->getFieldname(),
                        'fieldname' => $this->getName(),
                        'index' => $owner->getIndex(),
                    ],
                    $owner instanceof AbstractData => [
                        'ownerType' => 'objectbrick',
                        'ownerName' => $owner->getFieldname(),
                        'fieldname' => $this->getName(),
                        'index' => $owner->getType(),
                    ],
                    default => [],
                }];

                return $defaultValueGenerator->getValue($object, $this, $context);
            }
        }

        $configuredDefaultValue = $this->doGetDefaultValue($object, $params['context'] ?? []);

        // 4. we check first if we even want to work with default values.
        if ($this->isEmpty($configuredDefaultValue)) {
            return $configuredDefaultValue;
        }

        $class = match (true) {
            $owner instanceof Concrete => $owner->getClass(),
            $owner instanceof AbstractData => $owner->getObject()?->getClass(),
            default => null,
        };

        /*
         * 5. if inheritance is enabled and there is no parent value then take the default value.
         * 6. if inheritance is disabled, take the default value.
         */
        if ($class?->getAllowInherit()) {
            try {
                // make sure we get the inherited value of the parent
                $parentValue = DataObject\Service::useInheritedValues(true,
                    fn () => $owner?->getValueFromParent($this->getName(), []),
                );

                if (!$this->isEmpty($parentValue) || $parentValue === null) {
                    return null;
                }
            } catch (InheritanceParentNotFoundException) {
                // no data from parent available, use the default value
            }
        }

        return $configuredDefaultValue;
    }

    public function getDefaultValueGenerator(): string
    {
        return $this->defaultValueGenerator;
    }

    public function setDefaultValueGenerator(string $defaultValueGenerator): void
    {
        $this->defaultValueGenerator = $defaultValueGenerator;
    }
}
