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

namespace OpenDxp\Bundle\CoreBundle\Command\Definition\Import;

use Exception;
use OpenDxp\Logger;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition\CustomLayout;
use OpenDxp\Model\ModelInterface;
use Override;
use Symfony\Component\Console\Input\InputOption;

/**
 * @internal
 */
class CustomLayoutCommand extends AbstractStructureImportCommand
{
    #[Override]
    protected function configure(): void
    {
        parent::configure();

        $this->addOption(
            'class-name',
            'c',
            InputOption::VALUE_REQUIRED,
            'Object class name which is used for custom definition'
        );
    }

    /**
     * Get type.
     *
     */
    protected function getType(): string
    {
        return 'Customlayout';
    }

    /**
     * Get definition name from filename (e.g. custom_definition_Customer_export.json -> Customer).
     *
     *
     */
    protected function getDefinitionName(string $filename): ?string
    {
        $parts = [];
        if (preg_match('/^custom_definition_(.*)_export\.json$/', $filename, $parts) === 1) {
            return $parts[1];
        }

        return null;
    }

    /**
     * Try to load definition by name.
     *
     *
     *
     * @throws Exception
     */
    protected function loadDefinition(string $name): ?ModelInterface
    {
        return CustomLayout::getByName($name);
    }

    protected function createDefinition(string $name): ?ModelInterface
    {
        $className = $this->input->getOption('class-name');
        if ($className) {
            $class = DataObject\ClassDefinition::getByName($className);
            if ($class) {
                return CustomLayout::create(
                    [
                        'classId' => $class->getId(),
                        'name' => $name,
                    ]
                );
            }
        }

        return null;
    }

    protected function import(ModelInterface $definition, ?string $json = null): bool
    {
        if (!$definition instanceof CustomLayout) {
            return false;
        }

        $importData = json_decode($json, true);

        try {
            $layout = DataObject\ClassDefinition\Service::generateLayoutTreeFromArray($importData['layoutDefinitions'], true);

            $definition->setLayoutDefinitions($layout);
            $definition->setDescription($importData['description']);
            $definition->setUserModification(0);
            $definition->setUserOwner(0);

            $definition->save();

            return true;
        } catch (Exception $e) {
            Logger::error($e->getMessage());
        }

        return false;
    }
}
