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

namespace OpenDxp\Model\DataObject\Data;

use DeepCopy\DeepCopy;
use DeepCopy\Filter\SetNullFilter;
use DeepCopy\Matcher\PropertyNameMatcher;
use DeepCopy\Reflection\ReflectionHelper;
use OpenDxp\Cache\Core\CacheMarshallerInterface;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\AbstractModel;
use OpenDxp\Model\DataObject\OwnerAwareFieldInterface;
use OpenDxp\Model\DataObject\Traits\OwnerAwareFieldTrait;
use OpenDxp\Model\Element\AbstractElement;
use OpenDxp\Model\Element\DeepCopy\UnmarshalMatcher;
use OpenDxp\Model\Element\ElementDescriptor;
use OpenDxp\Model\Element\ElementDumpStateInterface;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use OpenDxp\Model\Version\SetDumpStateFilter;
use ReflectionProperty;
use Stringable;

class BlockElement extends AbstractModel implements OwnerAwareFieldInterface, CacheMarshallerInterface, Stringable
{
    use OwnerAwareFieldTrait;

    /**
     * @internal
     *
     */
    protected bool $needsRenewReferences = false;

    /**
     * BlockElement constructor.
     *
     */
    public function __construct(protected string $name, protected string $type, protected mixed $data)
    {
        $this->markMeDirty();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        if ($name !== $this->name) {
            $this->name = $name;
            $this->markMeDirty();
        }
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        if ($type !== $this->type) {
            $this->type = $type;
            $this->markMeDirty();
        }
    }

    public function getData(): mixed
    {
        if ($this->needsRenewReferences) {
            $this->needsRenewReferences = false;
            $this->renewReferences();
        }

        return $this->data;
    }

    public function setData(mixed $data): void
    {
        $this->data = $data;
        $this->markMeDirty();
    }

    protected function renewReferences(): void
    {
        $copier = new DeepCopy();
        $copier->skipUncloneable(true);
        $copier->addTypeFilter(
            new \DeepCopy\TypeFilter\ReplaceFilter(
                function ($currentValue) {
                    if ($currentValue instanceof ElementDescriptor) {
                        $cacheKey = $currentValue->getCacheKey();
                        $cacheKeyRenewed = $cacheKey . '_blockElementRenewed';

                        if (!RuntimeCache::isRegistered($cacheKeyRenewed)) {
                            if (RuntimeCache::isRegistered($cacheKey)) {
                                // we don't want the copy from the runtime but cache is fine
                                RuntimeCache::getInstance()->offsetUnset($cacheKey);
                            }
                            RuntimeCache::save(true, $cacheKeyRenewed);
                        }

                        return Service::getElementById($currentValue->getType(), $currentValue->getId());
                    }

                    return $currentValue;
                }
            ),
            new UnmarshalMatcher()
        );

        $copier->addFilter(new SetNullFilter(), new PropertyNameMatcher('_owner'));

        $copier->addFilter(new \DeepCopy\Filter\KeepFilter(), new class() implements \DeepCopy\Matcher\Matcher {
            /**
             * @param object $object
             * @param string $property
             *
             */
            public function matches($object, $property): bool
            {
                $reflectionProperty = null;
                if ($object instanceof Video) {
                    $reflectionProperty = ReflectionHelper::getProperty($object, 'data');
                }
                if ($object instanceof Hotspotimage) {
                    $reflectionProperty = ReflectionHelper::getProperty($object, 'image');
                }
                if ($object instanceof ExternalImage) {
                    $reflectionProperty = ReflectionHelper::getProperty($object, 'url');
                }

                if ($reflectionProperty instanceof ReflectionProperty) {
                    return !($reflectionProperty->getValue($object) instanceof ElementDescriptor);
                }

                return $object instanceof AbstractElement;
            }
        });

        $this->data = $copier->copy($this->data);
    }

    public function __toString(): string
    {
        return $this->name . '; ' . $this->type;
    }

    public function __wakeup(): void
    {
        $this->needsRenewReferences = true;

        if ($this->data instanceof OwnerAwareFieldInterface) {
            $this->data->_setOwner($this);
            $this->data->_setOwnerFieldname($this->getName());
            $this->data->_setOwnerLanguage(null);
        }
    }

    /**
     * @internal
     *
     */
    public function getNeedsRenewReferences(): bool
    {
        return $this->needsRenewReferences;
    }

    /**
     * @internal
     *
     */
    public function setNeedsRenewReferences(bool $needsRenewReferences): void
    {
        $this->needsRenewReferences = $needsRenewReferences;
    }

    public function setLanguage(string $language): void
    {
        $this->_language = $language;
    }

    public function marshalForCache(): mixed
    {
        $this->needsRenewReferences = true;

        $context = [
            'source' => __METHOD__,
            'conversion' => false,
        ];
        $copier = Service::getDeepCopyInstance($this, $context);
        $copier->addFilter(new SetDumpStateFilter(false), new \DeepCopy\Matcher\PropertyMatcher(ElementDumpStateInterface::class, ElementDumpStateInterface::DUMP_STATE_PROPERTY_NAME));

        $copier->addTypeFilter(
            new \DeepCopy\TypeFilter\ReplaceFilter(
                function ($currentValue) {
                    if ($currentValue instanceof ElementInterface) {
                        $elementType = Service::getElementType($currentValue);

                        return new ElementDescriptor($elementType, $currentValue->getId());
                    }

                    return $currentValue;
                }
            ),
            new \OpenDxp\Model\Element\DeepCopy\MarshalMatcher(null, null)
        );
        $copier->addFilter(new SetNullFilter(), new PropertyNameMatcher('_owner'));

        return $copier->copy($this);
    }
}
