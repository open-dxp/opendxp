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

namespace OpenDxp\Model\DataObject\Objectbrick;

use Exception;
use OpenDxp;
use OpenDxp\Cache;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\DataObject\ClassBuilder\PHPObjectBrickClassDumperInterface;
use OpenDxp\DataObject\ClassBuilder\PHPObjectBrickContainerClassDumperInterface;
use OpenDxp\Event\Model\DataObject\ObjectbrickDefinitionEvent;
use OpenDxp\Event\ObjectbrickDefinitionEvents;
use OpenDxp\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use OpenDxp\Logger;
use OpenDxp\Model;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\ClassDefinition\Data\FieldDefinitionEnrichmentInterface;
use OpenDxp\Tool;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @method \OpenDxp\Model\DataObject\Objectbrick\Definition\Dao getDao()
 * @method string getTableName(DataObject\ClassDefinition $class, bool $query = false)
 * @method void createUpdateTable(DataObject\ClassDefinition $class)
 * @method string getLocalizedTableName(DataObject\ClassDefinition $class, bool $query = false, string $language = 'en')
 */
class Definition extends Model\DataObject\Fieldcollection\Definition
{
    use Model\DataObject\ClassDefinition\Helper\VarExport;
    use DataObject\Traits\LocateFileTrait;
    use DataObject\Traits\FieldcollectionObjectbrickDefinitionTrait;
    use RecursionBlockingEventDispatchHelperTrait;

    public array $classDefinitions = [];

    private array $oldClassDefinitions = [];

    public function setClassDefinitions(array $classDefinitions): static
    {
        $this->classDefinitions = $classDefinitions;

        return $this;
    }

    public function getClassDefinitions(): array
    {
        return $this->classDefinitions;
    }

    #[\Override]
    public static function getByKey(string $key): ?Definition
    {
        $brick = null;
        $cacheKey = 'objectbrick_' . $key;

        try {
            $brick = RuntimeCache::get($cacheKey);
            if (!$brick) {
                throw new Exception('ObjectBrick in Registry is not valid');
            }
        } catch (Exception $e) {
            $def = new Definition();
            $def->setKey($key);
            $fieldFile = $def->getDefinitionFile();

            if (is_file($fieldFile)) {
                $brick = include $fieldFile;
                RuntimeCache::set($cacheKey, $brick);
            }
        }

        if ($brick) {
            return $brick;
        }

        return null;
    }

    /**
     * @throws Exception
     */
    private function checkTablenames(): void
    {
        $tables = [];
        $key = $this->getKey();
        if (!$this->getFieldDefinitions()) {
            return;
        }
        $isLocalized = (bool) $this->getFieldDefinition('localizedfields');

        $classDefinitions = $this->getClassDefinitions();
        $validLanguages = Tool::getValidLanguages();
        foreach ($classDefinitions as $classDef) {
            $classname = $classDef['classname'];

            $class = DataObject\ClassDefinition::getByName($classname);

            if (!$class) {
                Logger::error('class ' . $classname . " doesn't exist anymore");

                continue;
            }

            $tables[] = 'object_brick_query_' . $key .  '_' . $class->getId();
            $tables[] = 'object_brick_store_' . $key .  '_' . $class->getId();
            if ($isLocalized) {
                foreach ($validLanguages as $validLanguage) {
                    $tables[] = 'object_brick_localized_query_' . $key . '_' . $class->getId() . '_' . $validLanguage;
                    $tables[] = 'object_brick_localized_' . $key . '_' . $class->getId();
                }
            }
        }

        if ($tables) {
            $tablesLen = array_map(strlen(...), $tables);
            array_multisort($tablesLen, $tables);
            $longestTablename = end($tables);

            $length = strlen($longestTablename);
            if ($length > 64) {
                throw new Exception('table name ' . $longestTablename . ' would be too long. Max length is 64. Current length would be ' .  $length . '.');
            }
        }
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function save(bool $saveDefinitionFile = true): void
    {
        if (!$this->getKey()) {
            throw new Exception('A object-brick needs a key to be saved!');
        }

        if ($this->isForbiddenName()) {
            throw new Exception(sprintf('Invalid key for object-brick: %s', $this->getKey()));
        }

        if ($this->getParentClass() && !preg_match('/^[a-zA-Z_\x7f-\xff\\\][a-zA-Z0-9_\x7f-\xff\\\]*$/', $this->getParentClass())) {
            throw new Exception(sprintf('Invalid parentClass value for class definition: %s',
                $this->getParentClass()));
        }

        $this->checkTablenames();
        $this->checkContainerRestrictions();

        $isUpdate = file_exists($this->getDefinitionFile());

        if (!$isUpdate) {
            $this->dispatchEvent(new ObjectbrickDefinitionEvent($this), ObjectbrickDefinitionEvents::PRE_ADD);
        } else {
            $this->dispatchEvent(new ObjectbrickDefinitionEvent($this), ObjectbrickDefinitionEvents::PRE_UPDATE);
        }

        $fieldDefinitions = $this->getFieldDefinitions();
        foreach ($fieldDefinitions as $fd) {
            if ($fd->isForbiddenName()) {
                throw new Exception(sprintf('Forbidden name used for field definition: %s', $fd->getName()));
            }

            if ($fd instanceof DataObject\ClassDefinition\Data\DataContainerAwareInterface) {
                $fd->preSave($this);
            }
        }

        $newClassDefinitions = [];
        $classDefinitionsToDelete = [];

        foreach ($this->classDefinitions as $cl) {
            if (!isset($cl['deleted']) || !$cl['deleted']) {
                $newClassDefinitions[] = $cl;
            } else {
                $classDefinitionsToDelete[] = $cl;
            }
        }

        $this->classDefinitions = $newClassDefinitions;

        $this->generateClassFiles($saveDefinitionFile);

        $cacheKey = 'objectbrick_' . $this->getKey();
        // for localized fields getting a fresh copy
        RuntimeCache::set($cacheKey, $this);

        $this->createContainerClasses();
        $this->updateDatabase();

        foreach ($fieldDefinitions as $fd) {
            if ($fd instanceof DataObject\ClassDefinition\Data\DataContainerAwareInterface) {
                $fd->postSave($this);
            }
        }

        if (!$isUpdate) {
            $this->dispatchEvent(new ObjectbrickDefinitionEvent($this), ObjectbrickDefinitionEvents::POST_ADD);
        } else {
            $this->dispatchEvent(new ObjectbrickDefinitionEvent($this), ObjectbrickDefinitionEvents::POST_UPDATE);
        }
    }

    /**
     * @param DataObject\ClassDefinition\Data[] $fds
     *
     * @throws Exception
     */
    private function enforceBlockRules(array $fds, array $found = []): void
    {
        foreach ($fds as $fd) {
            $childParams = $found;
            if ($fd instanceof DataObject\ClassDefinition\Data\Block) {
                $childParams['block'] = true;
            } elseif ($fd instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                if ($found['block'] ?? false) {
                    throw new Exception('A localizedfield cannot be nested inside a block');
                }
            }
            if (method_exists($fd, 'getFieldDefinitions')) {
                $this->enforceBlockRules($fd->getFieldDefinitions(), $childParams);
            }
        }
    }

    private function checkContainerRestrictions(): void
    {
        $fds = $this->getFieldDefinitions();
        $this->enforceBlockRules($fds);
    }

    #[\Override]
    protected function generateClassFiles(bool $generateDefinitionFile = true): void
    {
        if ($generateDefinitionFile && !$this->isWritable()) {
            throw new DataObject\Exception\DefinitionWriteException();
        }

        $definitionFile = $this->getDefinitionFile();

        if ($generateDefinitionFile) {
            $this->cleanupOldFiles($definitionFile);

            /** @var self $clone */
            $clone = DataObject\Service::cloneDefinition($this);
            $clone->setDao(null);
            unset($clone->oldClassDefinitions);
            unset($clone->fieldDefinitions);

            DataObject\ClassDefinition::cleanupForExport($clone->layoutDefinitions);

            $exportedClass = var_export($clone, true);

            $data = '<?php';
            $data .= "\n\n";
            $data .= $this->getInfoDocBlock();
            $data .= "\n\n";

            $data .= 'return ' . $exportedClass . ";\n";

            $filesystem = new Filesystem();
            $filesystem->dumpFile($definitionFile, $data);
        }

        OpenDxp::getContainer()->get(PHPObjectBrickClassDumperInterface::class)->dumpPHPClasses($this);
    }

    private function buildClassList(array $definitions): array
    {
        $result = [];
        foreach ($definitions as $definition) {
            $result[] = $definition['classname'] . '-' . $definition['fieldname'];
        }

        return $result;
    }

    /**
     * Returns a list of classes which need to be "rebuild" because they are affected of changes.
     */
    private function getClassesToCleanup(Definition $oldObject): array
    {
        $oldDefinitions = $oldObject->getClassDefinitions() ?: [];
        $newDefinitions = $this->getClassDefinitions() ?: [];

        $old = $this->buildClassList($oldDefinitions);
        $new = $this->buildClassList($newDefinitions);

        $diff1 = array_diff($old, $new);
        $diff2 = array_diff($new, $old);

        $diff = [...$diff1, ...$diff2];
        $result = [];
        foreach ($diff as $item) {
            $parts = explode('-', $item);
            $result[] = ['classname' => $parts[0], 'fieldname' => $parts[1]];
        }

        return $result;
    }

    private function cleanupOldFiles(string $serializedFilename): void
    {
        $oldObject = null;
        $this->oldClassDefinitions = [];
        if (file_exists($serializedFilename)) {
            $oldObject = include $serializedFilename;
        }

        if ($oldObject && !empty($oldObject->classDefinitions)) {
            $classlist = $this->getClassesToCleanup($oldObject);

            foreach ($classlist as $cl) {
                $this->oldClassDefinitions[$cl['classname']] = $cl['classname'];
                $class = DataObject\ClassDefinition::getByName($cl['classname']);
                if ($class) {
                    $path = $this->getContainerClassFolder($class->getName());
                    @unlink($path . '/' . ucfirst($cl['fieldname'] . '.php'));

                    foreach ($class->getFieldDefinitions() as $fieldDef) {
                        if ($fieldDef instanceof DataObject\ClassDefinition\Data\Objectbricks) {
                            $allowedTypes = $fieldDef->getAllowedTypes();
                            $idx = array_search($this->getKey(), $allowedTypes);
                            if ($idx !== false) {
                                array_splice($allowedTypes, $idx, 1);
                            }
                            $fieldDef->setAllowedTypes($allowedTypes);
                        }
                    }

                    $class->save();
                }
            }
        }
    }

    /**
     * Update Database according to class-definition
     */
    private function updateDatabase(): void
    {
        $processedClasses = [];
        foreach ($this->classDefinitions as $cl) {
            unset($this->oldClassDefinitions[$cl['classname']]);

            if (empty($processedClasses[$cl['classname']])) {
                $class = DataObject\ClassDefinition::getByName($cl['classname']);
                $this->getDao()->createUpdateTable($class);
                $processedClasses[$cl['classname']] = true;
            }
        }

        foreach ($this->oldClassDefinitions as $cl) {
            $class = DataObject\ClassDefinition::getByName($cl);
            if ($class) {
                $this->getDao()->delete($class);

                foreach ($class->getFieldDefinitions() as $fieldDef) {
                    if ($fieldDef instanceof DataObject\ClassDefinition\Data\Objectbricks) {
                        $allowedTypes = $fieldDef->getAllowedTypes();
                        $idx = array_search($this->getKey(), $allowedTypes);
                        if ($idx !== false) {
                            array_splice($allowedTypes, $idx, 1);
                        }
                        $fieldDef->setAllowedTypes($allowedTypes);
                    }
                }

                $class->save();
            }
        }
    }

    /**
     * @internal
     */
    public function getAllowedTypesWithFieldname(DataObject\ClassDefinition $class): array
    {
        $result = [];
        $fieldDefinitions = $class->getFieldDefinitions();
        foreach ($fieldDefinitions as $fd) {
            if (!$fd instanceof DataObject\ClassDefinition\Data\Objectbricks) {
                continue;
            }

            $allowedTypes = $fd->getAllowedTypes() ?: [];
            foreach ($allowedTypes as $allowedType) {
                $result[] = $fd->getName() . '-' . $allowedType;
            }
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    private function createContainerClasses(): void
    {
        foreach ($this->classDefinitions as $cl) {
            $class = DataObject\ClassDefinition::getByName($cl['classname']);
            if (!$class) {
                throw new Exception('Could not load class ' . $cl['classname']);
            }

            $fd = $class->getFieldDefinition($cl['fieldname']);
            if (!$fd instanceof DataObject\ClassDefinition\Data\Objectbricks) {
                throw new Exception('Could not resolve field definition for ' . $cl['fieldname']);
            }

            $old = $this->getAllowedTypesWithFieldname($class);

            $allowedTypes = $fd->getAllowedTypes() ?: [];

            if (!in_array($this->key, $allowedTypes)) {
                $allowedTypes[] = $this->key;
            }

            $fd->setAllowedTypes($allowedTypes);
            $new = $this->getAllowedTypesWithFieldname($class);

            if (array_diff($new, $old) || array_diff($old, $new)) {
                $class->save();
            } else {
                // still, the brick fields definitions could have changed.
                Cache::clearTag('class_'.$class->getId());
                Logger::debug('Objectbrick ' . $this->getKey() . ', no change for class ' . $class->getName());
            }
        }
        OpenDxp::getContainer()->get(PHPObjectBrickContainerClassDumperInterface::class)->dumpContainerClasses($this);
    }

    /**
     * @internal
     */
    public function getContainerClassName(string $classname, string $fieldname): string
    {
        return ucfirst($fieldname);
    }

    /**
     * @internal
     */
    public function getContainerNamespace(string $classname, string $fieldname): string
    {
        return 'OpenDxp\\Model\\DataObject\\' . ucfirst($classname);
    }

    /**
     * @internal
     */
    public function getContainerClassFolder(string $classname): string
    {
        return OPENDXP_CLASS_DEFINITION_DIRECTORY . '/DataObject/' . ucfirst($classname);
    }

    /**
     * Delete Brick Definition
     *
     * @throws DataObject\Exception\DefinitionWriteException
     */
    #[\Override]
    public function delete(): void
    {
        if (!$this->isWritable() && file_exists($this->getDefinitionFile())) {
            throw new DataObject\Exception\DefinitionWriteException();
        }
        $this->dispatchEvent(new ObjectbrickDefinitionEvent($this), ObjectbrickDefinitionEvents::PRE_DELETE);
        @unlink($this->getDefinitionFile());
        @unlink($this->getPhpClassFile());

        $processedClasses = [];
        foreach ($this->classDefinitions as $cl) {
            unset($this->oldClassDefinitions[$cl['classname']]);

            if (!isset($processedClasses[$cl['classname']])) {
                $processedClasses[$cl['classname']] = true;
                $class = DataObject\ClassDefinition::getByName($cl['classname']);
                if ($class instanceof DataObject\ClassDefinition) {
                    $this->getDao()->delete($class);

                    foreach ($class->getFieldDefinitions() as $fieldDef) {
                        if ($fieldDef instanceof DataObject\ClassDefinition\Data\Objectbricks) {
                            $allowedTypes = $fieldDef->getAllowedTypes();
                            $idx = array_search($this->getKey(), $allowedTypes);
                            if ($idx !== false) {
                                array_splice($allowedTypes, $idx, 1);
                            }
                            $fieldDef->setAllowedTypes($allowedTypes);
                        }
                    }

                    $class->save();
                }
            }
        }

        // update classes
        $classList = new DataObject\ClassDefinition\Listing();
        $classes = $classList->load();
        foreach ($classes as $class) {
            foreach ($class->getFieldDefinitions() as $fieldDef) {
                if (!$fieldDef instanceof DataObject\ClassDefinition\Data\Objectbricks) {
                    continue;
                }
                if (!in_array($this->getKey(), $fieldDef->getAllowedTypes())) {
                    continue;
                }
                break;
            }
        }

        $this->dispatchEvent(new ObjectbrickDefinitionEvent($this), ObjectbrickDefinitionEvents::POST_DELETE);
    }

    #[\Override]
    protected function doEnrichFieldDefinition(Data $fieldDefinition, array $context = []): Data
    {
        if ($fieldDefinition instanceof FieldDefinitionEnrichmentInterface) {
            $context['containerType'] = 'objectbrick';
            $context['containerKey'] = $this->getKey();
            $fieldDefinition = $fieldDefinition->enrichFieldDefinition($context);
        }

        return $fieldDefinition;
    }

    /**
     * @internal
     */
    #[\Override]
    public function isWritable(): bool
    {
        return (bool) ($_SERVER['OPENDXP_CLASS_DEFINITION_WRITABLE'] ?? !str_starts_with($this->getDefinitionFile(), OPENDXP_CUSTOM_CONFIGURATION_DIRECTORY));
    }

    /**
     * @internal
     */
    #[\Override]
    public function getDefinitionFile(?string $key = null): string
    {
        return $this->locateDefinitionFile($key ?? $this->getKey(), 'objectbricks/%s.php');
    }

    /**
     * @internal
     */
    #[\Override]
    public function getPhpClassFile(): string
    {
        return $this->locateFile(ucfirst($this->getKey()), 'DataObject/Objectbrick/Data/%s.php');
    }
}
