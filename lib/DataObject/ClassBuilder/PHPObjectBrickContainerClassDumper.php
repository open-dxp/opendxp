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

namespace OpenDxp\DataObject\ClassBuilder;

use OpenDxp\Model\DataObject\Objectbrick\Definition;
use Symfony\Component\Filesystem\Filesystem;

class PHPObjectBrickContainerClassDumper implements PHPObjectBrickContainerClassDumperInterface
{
    public function __construct(
        protected ObjectBrickContainerClassBuilderInterface $classBuilder,
        protected Filesystem $filesystem
    ) {
    }

    public function dumpContainerClasses(Definition $definition): void
    {
        $objectClassesFolders = array_filter(array_unique(array_map(realpath(...), [
            OPENDXP_CLASS_DEFINITION_DIRECTORY,
            OPENDXP_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY,
        ])));
        $containerDefinition = [];

        foreach ($definition->getClassDefinitions() as $cl) {
            $containerDefinition[$cl['classname']][$cl['fieldname']][] = $definition->getKey();
        }

        $list = new Definition\Listing();
        $list = $list->load();
        foreach ($list as $def) {
            if ($definition->getKey() !== $def->getKey()) {
                $classDefinitions = $def->getClassDefinitions();
                if (!empty($classDefinitions)) {
                    foreach ($classDefinitions as $cl) {
                        $containerDefinition[$cl['classname']][$cl['fieldname']][] = $def->getKey();
                    }
                }
            }
        }

        $includedFiles = [];

        foreach ($containerDefinition as $classId => $cd) {
            foreach ($objectClassesFolders as $objectClassesFolder) {
                $file = $objectClassesFolder . '/definition_' . $classId . '.php';
                if (!file_exists($file)) {
                    continue;
                }

                $realFile = realpath($file);

                if (isset($includedFiles[$realFile])) {
                    continue;
                }

                $includedFiles[$realFile] = true;
                $class = include $file;

                if (!$class) {
                    continue;
                }

                foreach ($cd as $fieldname => $brickKeys) {
                    $containerClass = $this->classBuilder->buildContainerClass($definition, $class, $fieldname, $brickKeys);
                    $folder = $definition->getContainerClassFolder($class->getName());

                    if (!is_dir($folder)) {
                        $this->filesystem->mkdir($folder, 0775);
                    }

                    $file = $folder . '/' . ucfirst($fieldname) . '.php';
                    $this->filesystem->dumpFile($file, $containerClass);
                }
            }
        }
    }
}
