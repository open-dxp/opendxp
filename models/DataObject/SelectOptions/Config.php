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

namespace OpenDxp\Model\DataObject\SelectOptions;

use Exception;
use InvalidArgumentException;
use JsonSerializable;
use OpenDxp;
use OpenDxp\Bundle\CoreBundle\OptionsProvider\SelectOptionsOptionsProvider;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\DataObject\ClassBuilder\PHPSelectOptionsEnumDumperInterface;
use OpenDxp\Helper\ReservedWordsHelper;
use OpenDxp\Model\AbstractModel;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Classificationstore;
use OpenDxp\Model\DataObject\Fieldcollection;
use OpenDxp\Model\DataObject\Objectbrick;
use OpenDxp\Model\DataObject\Traits\LocateFileTrait;
use OpenDxp\Model\Exception\NotFoundException;
use RuntimeException;

/**
 * @method bool isWriteable()
 * @method string getWriteTarget()
 * @method void delete()
 * @method Config\Dao getDao()
 */
final class Config extends AbstractModel implements JsonSerializable
{
    use LocateFileTrait;

    public const string PROPERTY_ID = 'id';

    public const string PROPERTY_GROUP = 'group';

    public const string PROPERTY_USE_TRAITS = 'useTraits';

    public const string PROPERTY_IMPLEMENTS_INTERFACES = 'implementsInterfaces';

    public const string PROPERTY_SELECT_OPTIONS = 'selectOptions';

    protected string $id;

    protected ?string $group = null;

    protected string $useTraits = '';

    protected string $implementsInterfaces = '';

    /**
     * @var Data\SelectOption[]
     */
    protected array $selectOptions = [];

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setId(string $id): static
    {
        $reservedWordsHelper = new ReservedWordsHelper();
        if ($reservedWordsHelper->isReservedWord($id)) {
            throw new InvalidArgumentException(
                'ID must not be one of reserved words: ' . implode(', ', $reservedWordsHelper->getAllReservedWords()),
                1677241981466
            );
        }

        $this->id = $id;

        return $this;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    /**
     * @return $this
     */
    public function setGroup(?string $group): static
    {
        $this->group = $group;

        return $this;
    }

    public function hasGroup(): bool
    {
        return !empty($this->group);
    }

    public function getUseTraits(): string
    {
        return $this->useTraits;
    }

    /**
     * @return $this
     */
    public function setUseTraits(string $useTraits): static
    {
        $this->useTraits = $useTraits;

        return $this;
    }

    public function getImplementsInterfaces(): string
    {
        return $this->implementsInterfaces;
    }

    /**
     * @return $this
     */
    public function setImplementsInterfaces(string $implementsInterfaces): static
    {
        $this->implementsInterfaces = $implementsInterfaces;

        return $this;
    }

    /**
     * @return Data\SelectOption[]
     */
    public function getSelectOptions(): array
    {
        return $this->selectOptions;
    }

    /**
     * @return array<string, string>[]
     */
    public function getSelectOptionsAsData(): array
    {
        return array_map(
            fn (Data\SelectOption $selectOption) => $selectOption->toArray(),
            $this->getSelectOptions()
        );
    }

    /**
     * @return $this
     */
    public function setSelectOptions(Data\SelectOption ...$selectOptions): static
    {
        $this->selectOptions = $selectOptions;

        return $this;
    }

    /**
     * @return $this
     */
    public function setSelectOptionsFromData(array $selectOptionsData): static
    {
        $selectOptions = [];
        foreach ($selectOptionsData as $selectOptionData) {
            $selectOptions[] = Data\SelectOption::createFromData($selectOptionData);
        }

        return $this->setSelectOptions(...$selectOptions);
    }

    public function hasSelectOptions(): bool
    {
        return $this->selectOptions !== [];
    }

    public static function getById(string $id): ?Config
    {
        $cacheKey = self::getCacheKey($id);

        try {
            $selectOptions = RuntimeCache::get($cacheKey);
            if (!$selectOptions instanceof self) {
                throw new RuntimeException('Select options in registry is invalid', 1678353750987);
            }
        } catch (Exception) {
            try {
                $selectOptions = new self();
                /** @var Config\Dao $dao */
                $dao = $selectOptions->getDao();
                $dao->getById($id);
                RuntimeCache::set($cacheKey, $selectOptions);
            } catch (NotFoundException) {
                return null;
            }
        }

        return $selectOptions;
    }

    protected static function getCacheKey(string $key): string
    {
        return 'selectoptions_' . $key;
    }

    public static function createFromData(array $data): static
    {
        // Check whether ID is available
        $id = $data[self::PROPERTY_ID] ?? null;
        if (empty($id)) {
            throw new RuntimeException('ID is mandatory for select options definition', 1676646778230);
        }

        $group = $data[self::PROPERTY_GROUP] ?? null;
        $useTraits = $data[self::PROPERTY_USE_TRAITS] ?? '';
        $implementsInterfaces = $data[self::PROPERTY_IMPLEMENTS_INTERFACES] ?? '';
        $selectOptionsData = $data[self::PROPERTY_SELECT_OPTIONS] ?? [];

        return (new self())
            ->setId($id)
            ->setGroup($group)
            ->setUseTraits($useTraits)
            ->setImplementsInterfaces($implementsInterfaces)
            ->setSelectOptionsFromData($selectOptionsData);
    }

    public function save(): void
    {
        $this->getDao()->save();
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            self::PROPERTY_ID => $this->getId(),
            self::PROPERTY_GROUP => $this->getGroup(),
            self::PROPERTY_USE_TRAITS => $this->getUseTraits(),
            self::PROPERTY_IMPLEMENTS_INTERFACES => $this->getImplementsInterfaces(),
            self::PROPERTY_SELECT_OPTIONS => $this->getSelectOptions(),
        ];
    }

    /**
     * @return array<string, string[]> Class name as key and field names as value
     */
    public function getFieldsUsedIn(): array
    {
        $definitions = [
            ...(new ClassDefinition\Listing())->load(),
            ...(new Fieldcollection\Definition\Listing())->load(),
            ...(new Objectbrick\Definition\Listing())->load(),
        ];

        $fieldsUsedIn = [];
        foreach ($definitions as $definition) {
            $prefix = match ($definition::class) {
                ClassDefinition::class => 'Class',
                Fieldcollection\Definition::class => 'Field Collection',
                Objectbrick\Definition::class => 'Objectbrick',
                default => 'Unknown',
            };

            $fieldsUsedIn = [
                ...$fieldsUsedIn,
                ...$this->getFieldsUsedInClass($definition, $prefix),
            ];
        }

        // Add classification store select fields
        foreach ((new Classificationstore\KeyConfig\Listing())->load() as $keyConfiguration) {
            $fieldDefinition = Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfiguration);
            if ($this->isConfiguredAsOptionsProvider($fieldDefinition)) {
                $fieldsUsedIn['Classification Store ' . $keyConfiguration->getStoreId()][] = $fieldDefinition->getName();
            }
        }

        return $fieldsUsedIn;
    }

    /**
     * @return array<string, string[]>
     */
    protected function getFieldsUsedInClass(
        ClassDefinition|Fieldcollection\Definition|Objectbrick\Definition $definition,
        string $prefix
    ): array {
        $definitionName = method_exists($definition, 'getName') ? $definition->getName() : $definition->getKey();

        $fieldsUsedIn = [];
        foreach ($this->getAllFieldDefinitions($definition) as $fieldDefinition) {
            if ($this->isConfiguredAsOptionsProvider($fieldDefinition)) {
                $fieldsUsedIn[$prefix . ' ' . $definitionName][] = $fieldDefinition->getName();
            }
        }

        return $fieldsUsedIn;
    }

    protected function isConfiguredAsOptionsProvider(?ClassDefinition\Data $fieldDefinition): bool
    {
        if (
            !$fieldDefinition instanceof \OpenDxp\Model\DataObject\ClassDefinition\Data
            || !$fieldDefinition instanceof ClassDefinition\Data\OptionsProviderInterface
            || empty($fieldDefinition->getOptionsProviderType())
            || $fieldDefinition->getOptionsProviderType() === ClassDefinition\Data\OptionsProviderInterface::TYPE_CONFIGURE
        ) {
            return false;
        }

        $configuredClass = trim($fieldDefinition->getOptionsProviderClass() ?? '', '\\');
        $configuredEnumName = trim($fieldDefinition->getOptionsProviderData() ?? '', '\\ ');

        $expectedEnumNames = [
            $this->getEnumName(),
            $this->getEnumName(true),
        ];

        return $configuredClass === SelectOptionsOptionsProvider::class
            && in_array($configuredEnumName, $expectedEnumNames, true);
    }

    /**
     * @return ClassDefinition\Data[]
     */
    protected function getAllFieldDefinitions(
        ClassDefinition|Fieldcollection\Definition|Objectbrick\Definition $definition
    ): array {
        $fieldDefinitions = $definition->getFieldDefinitions(['suppressEnrichment' => true]);
        $localizedFieldDefinition = $definition->getFieldDefinition('localizedfields', ['suppressEnrichment' => true]);
        if ($localizedFieldDefinition instanceof ClassDefinition\Data\Localizedfields) {
            return [
                ...$fieldDefinitions,
                ...$localizedFieldDefinition->getFieldDefinitions(['suppressEnrichment' => true]),
            ];
        }

        return $fieldDefinitions;
    }

    /**
     * @throws Exception if configured interfaces or traits don't exist
     *
     * @internal
     */
    public function generateEnumFiles(): void
    {
        OpenDxp::getContainer()->get(PHPSelectOptionsEnumDumperInterface::class)->dumpPHPEnum($this);
    }

    /**
     * @internal
     */
    public function getPhpClassFile(): string
    {
        return $this->locateFile(ucfirst($this->getId()), 'DataObject/SelectOptions/%s.php');
    }

    public function getEnumName(bool $prependNamespace = false): string
    {
        $className = $this->getId();
        if (!$prependNamespace) {
            return $className;
        }

        return $this->getNamespace() . '\\' . $className;
    }

    public function getNamespace(): string
    {
        return 'OpenDxp\\Model\\DataObject\\SelectOptions';
    }
}
