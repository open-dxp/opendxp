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

namespace OpenDxp\Model\DataObject\ClassDefinition;

use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\ClassDefinitionInterface;

class ClassDefinitionManager
{
    public const SAVED = 'saved';

    public const CREATED = 'created';

    public const SKIPPED = 'skipped';

    public const DELETED = 'deleted';

    /**
     * Delete all classes from db
     *
     * @return list<array{string, string, string}>
     */
    public function cleanUpDeletedClassDefinitions(): array
    {
        $db = \OpenDxp\Db::get();
        $classes = $db->fetchAllAssociative('SELECT * FROM classes');
        $deleted = [];

        foreach ($classes as $class) {
            $id = $class['id'];
            $name = $class['name'];

            $cls = new ClassDefinition();
            $cls->setId($id);
            $cls->setName($name);
            $definitionFile = $cls->getDefinitionFile();

            if (!file_exists($definitionFile)) {
                $deleted[] = [$name, $id, self::DELETED];

                //ClassDefinition doesn't exist anymore, therefore we delete it
                $cls->delete();
            }
        }

        return $deleted;
    }

    /**
     * Updates all classes from OPENDXP_CLASS_DEFINITION_DIRECTORY
     *
     * @param bool $force whether to always update no matter if the model definition changed or not
     *
     * @return list<array{string, string, string}>
     */
    public function createOrUpdateClassDefinitions(bool $force = false): array
    {
        $objectClassesFolders = array_filter(array_unique(array_map(realpath(...), [
            OPENDXP_CLASS_DEFINITION_DIRECTORY,
            OPENDXP_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY,
        ])));

        $changes = [];
        $includedFiles = [];

        foreach ($objectClassesFolders as $objectClassesFolder) {
            $files = glob($objectClassesFolder . '/*.php');
            foreach ($files as $file) {
                $realFile = realpath($file);

                if (isset($includedFiles[$realFile])) {
                    continue;
                }

                $includedFiles[$realFile] = true;
                $class = include $file;

                if ($class instanceof ClassDefinitionInterface) {
                    $existingClass = ClassDefinition::getByName($class->getName());

                    if ($existingClass instanceof ClassDefinitionInterface) {
                        $classSaved = $this->saveClass($existingClass, false, $force);
                        $changes[] = [$existingClass->getName(), $existingClass->getId(), $classSaved ? self::SAVED : self::SKIPPED];
                    } else {
                        //when creating, it should always save like as forced
                        $classSaved = $this->saveClass($class, false, true);
                        $changes[] = [$class->getName(), $class->getId(), $classSaved ? self::CREATED : self::SKIPPED];
                    }
                }
            }
        }

        return $changes;
    }

    /**
     * @return bool whether the class was saved or not
     */
    public function saveClass(ClassDefinitionInterface $class, bool $saveDefinitionFile, bool $force = false): bool
    {
        $shouldSave = $force;

        if (!$force) {
            $db = \OpenDxp\Db::get();

            $definitionModificationDate = null;

            if ($classId = $class->getId()) {
                $definitionModificationDate = $db->fetchOne('SELECT definitionModificationDate FROM classes WHERE id = ?;', [$classId]);
            }

            if (!$definitionModificationDate || $definitionModificationDate !== $class->getModificationDate()) {
                $shouldSave = true;
            }
        }

        if ($shouldSave) {
            $class->save($saveDefinitionFile);
        }

        return $shouldSave;
    }
}
