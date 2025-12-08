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

namespace OpenDxp\Model\Document\Editable;

use InvalidArgumentException;
use OpenDxp;
use OpenDxp\Bundle\PersonalizationBundle\Model\Document\Targeting\TargetingDocumentInterface;
use OpenDxp\Bundle\PersonalizationBundle\Targeting\Document\DocumentTargetingConfigurator;
use OpenDxp\Document\Editable\EditableHandler;
use OpenDxp\Logger;
use OpenDxp\Model;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element;

/**
 * @method \OpenDxp\Model\Document\Editable\Dao getDao()
 */
class Renderlet extends Model\Document\Editable implements IdRewriterInterface, EditmodeDataInterface, LazyLoadingInterface
{
    /**
     * Known config keys of this editable.
     * These are passed to the controller as attributes.
     * Everything else is passed to the controller as query parameters.
     */
    private const array CONFIG_KEYS = [
        'controller' => true,
        'template' => true,
        'className' => true,
        'height' => true,
        'width' => true,
        'reload' => true,
        'title' => true,
        'type' => true,
        'class' => true,
    ];

    /**
     * Contains the ID of the linked object
     *
     * @internal
     *
     */
    protected ?int $id = null;

    /**
     * Contains the object
     *
     * @internal
     */
    protected Document|Asset|null|DataObject|Element\ElementDescriptor $o = null;

    /**
     * Contains the type
     *
     * @internal
     *
     */
    protected ?string $type = null;

    /**
     * Contains the subtype
     *
     * @internal
     *
     */
    protected ?string $subtype = null;

    public function getType(): string
    {
        return 'renderlet';
    }

    public function getData(): mixed
    {
        return [
            'id' => $this->id,
            'type' => $this->getObjectType(),
            'subtype' => $this->subtype,
        ];
    }

    public function getDataEditmode(): ?array
    {
        if ($this->o instanceof Element\ElementInterface) {
            return [
                'id' => $this->id,
                'type' => $this->getObjectType(),
                'subtype' => $this->subtype,
            ];
        }

        return null;
    }

    public function frontend()
    {
        // TODO inject services via DI when editables are built through container
        $container = OpenDxp::getContainer();

        if (empty($this->config['controller']) && !empty($this->config['template'])) {
            $this->config['controller'] = $container->getParameter('opendxp.documents.default_controller');
        }

        if (empty($this->config['controller'])) {
            // this can be the case e.g. in \OpenDxp\Model\Search\Backend\Data::setDataFromElement() where
            // this method is called without the config, so it would just render the default controller with the default template
            return '';
        }

        $this->load();

        if ($this->o instanceof Element\ElementInterface) {
            if (method_exists($this->o, 'isPublished') && !$this->o->isPublished()) {
                return '';
            }

            //Personalization & Targeting Specific
            // apply best matching target group (if any)
            // @phpstan-ignore-next-line
            if ($container->has(DocumentTargetingConfigurator::class)
                && $this->o instanceof TargetingDocumentInterface) {
                $targetingConfigurator = $container->get(DocumentTargetingConfigurator::class);
                $targetingConfigurator->configureTargetGroup($this->o);
            }

            $attributes = [
                'template' => $this->config['template'] ?? null,
                'id' => $this->id,
                'type' => $this->type,
                'subtype' => $this->subtype,
                'opendxp_request_source' => 'renderlet',
            ];
            $query = [];

            foreach ($this->config as $key => $value) {
                if ('controller' !== $key && !array_key_exists($key, $attributes)) {
                    // Todo: pass only config keys as attributes in OpenDxp 2
                    $attributes[$key] = $value;
                }

                if (!isset(self::CONFIG_KEYS[$key])) {
                    $query[$key] = $value;
                }
            }

            return $container->get(EditableHandler::class)->renderAction(
                $this->config['controller'],
                $attributes,
                $query,
            );
        }

        return '';
    }

    /**
     *
     *
     * @return $this
     */
    public function setDataFromResource(mixed $data): static
    {
        $unserializedData = $this->getUnserializedData($data) ?? [];

        foreach (['id', 'type', 'subtype'] as $key) {
            if (!array_key_exists($key, $unserializedData)) {
                throw new InvalidArgumentException("Key '{$key}' is missing in the data array.");
            }
        }

        $this->id = $unserializedData['id'];
        $this->type = (string) $unserializedData['type'];
        $this->subtype = $unserializedData['subtype'];

        $this->setElement();

        return $this;
    }

    /**
     *
     *
     * @return $this
     */
    public function setDataFromEditmode(mixed $data): static
    {
        if (is_array($data) && isset($data['id'])) {
            $this->id = $data['id'];
            $this->type = $data['type'];
            $this->subtype = $data['subtype'];

            $this->setElement();
        }

        return $this;
    }

    /**
     * Sets the element by the data stored for the object
     *
     * @return $this
     */
    public function setElement(): static
    {
        if ($this->type && $this->id) {
            $this->o = Element\Service::getElementById($this->type, $this->id);
        }

        return $this;
    }

    #[\Override]
    public function resolveDependencies(): array
    {
        $this->load();

        $dependencies = [];

        if ($this->o instanceof Element\ElementInterface) {
            $elementType = Element\Service::getElementType($this->o);
            $key = $elementType . '_' . $this->o->getId();

            $dependencies[$key] = [
                'id' => $this->o->getId(),
                'type' => $elementType,
            ];
        }

        return $dependencies;
    }

    /**
     * get correct type of object as string
     */
    private function getObjectType(?Element\ElementInterface $object = null): ?string
    {
        $this->load();

        if (!$object) {
            $object = $this->o;
        }
        if ($object instanceof Element\ElementInterface) {
            return Element\Service::getElementType($object);
        }

        return null;
    }

    public function isEmpty(): bool
    {
        $this->load();
        return !$this->o instanceof Element\ElementInterface;
    }

    #[\Override]
    public function checkValidity(): bool
    {
        $sane = true;
        if ($this->id) {
            $el = Element\Service::getElementById($this->type, $this->id);
            if (!$el instanceof Element\ElementInterface) {
                $sane = false;
                Logger::notice('Detected insane relation, removing reference to non existent '.$this->type.' with id ['.$this->id.']');
                $this->id = null;
                $this->type = null;
                $this->o = null;
                $this->subtype = null;
            }
        }

        return $sane;
    }

    #[\Override]
    public function __sleep(): array
    {
        $finalVars = [];
        $parentVars = parent::__sleep();
        $blockedVars = ['o'];
        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    public function load(): void
    {
        if (!$this->o) {
            $this->setElement();
        }
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * @return $this
     */
    public function setO(DataObject|Asset|Document|null $o): static
    {
        $this->o = $o;

        return $this;
    }

    public function getO(): DataObject|Asset|Document|null
    {
        return $this->o;
    }

    /**
     * @return $this
     */
    public function setSubtype(string $subtype): static
    {
        $this->subtype = $subtype;

        return $this;
    }

    public function getSubtype(): ?string
    {
        return $this->subtype;
    }

    public function rewriteIds(array $idMapping): void
    {
        $type = (string) $this->type;
        if ($type && array_key_exists($this->type, $idMapping) && array_key_exists($this->getId(), $idMapping[$this->type])) {
            $this->setId($idMapping[$this->type][$this->getId()]);
            $this->setO(null);
        }
    }
}
