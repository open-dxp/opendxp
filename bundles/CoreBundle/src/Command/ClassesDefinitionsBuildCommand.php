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

namespace OpenDxp\Bundle\CoreBundle\Command;

use OpenDxp\Cache;
use OpenDxp\Console\AbstractCommand;
use OpenDxp\DataObject\ClassBuilder\PHPClassDumperInterface;
use OpenDxp\DataObject\ClassBuilder\PHPFieldCollectionClassDumperInterface;
use OpenDxp\DataObject\ClassBuilder\PHPObjectBrickClassDumperInterface;
use OpenDxp\DataObject\ClassBuilder\PHPObjectBrickContainerClassDumperInterface;
use OpenDxp\DataObject\ClassBuilder\PHPSelectOptionsEnumDumperInterface;
use OpenDxp\Model\DataObject;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'opendxp:build:classes',
    description: 'rebuilds php files for classes, field collections and object bricks
    based on updated var/classes/definition_*.php files'
)]
class ClassesDefinitionsBuildCommand extends AbstractCommand
{
    public function __construct(
        protected PHPClassDumperInterface $classDumper,
        protected PHPFieldCollectionClassDumperInterface $collectionClassDumper,
        protected PHPObjectBrickClassDumperInterface $brickClassDumper,
        protected PHPObjectBrickContainerClassDumperInterface $brickContainerClassDumper,
        protected PHPSelectOptionsEnumDumperInterface $selectOptionsEnumDumper,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cacheStatus = Cache::isEnabled();
        Cache::disable();

        $objectClassesFolders = array_filter(array_unique(array_map(realpath(...), [
            OPENDXP_CLASS_DEFINITION_DIRECTORY,
            OPENDXP_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY,
        ])));

        $includedFiles = [];

        foreach ($objectClassesFolders as $objectClassesFolder) {
            $files = glob($objectClassesFolder.'/*.php');

            foreach ($files as $file) {
                $realFile = realpath($file);

                if (isset($includedFiles[$realFile])) {
                    continue;
                }

                $includedFiles[$realFile] = true;
                $class = include $file;

                $this->classDumper->dumpPHPClasses($class);
            }
        }

        $list = new DataObject\Objectbrick\Definition\Listing();
        $list = $list->load();
        foreach ($list as $brickDefinition) {
            $this->brickClassDumper->dumpPHPClasses($brickDefinition);
            $this->brickContainerClassDumper->dumpContainerClasses($brickDefinition);
        }

        $list = new DataObject\Fieldcollection\Definition\Listing();
        $list = $list->load();
        foreach ($list as $fcDefinition) {
            $this->collectionClassDumper->dumpPHPClass($fcDefinition);
        }

        $selectOptionConfigurations = new DataObject\SelectOptions\Config\Listing();
        foreach ($selectOptionConfigurations as $selectOptionConfiguration) {
            $this->selectOptionsEnumDumper->dumpPHPEnum($selectOptionConfiguration);
        }

        if ($cacheStatus) {
            Cache::enable();
        }

        return 0;
    }
}
