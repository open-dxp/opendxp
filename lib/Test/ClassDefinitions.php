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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Test;

use OpenDxp\Model\DataObject\ClassDefinition;
use RuntimeException;

final class ClassDefinitions
{
    public static function install(string $name, string $definition): ClassDefinition
    {
        $json = file_get_contents($definition);

        if ($json === false) {
            throw new RuntimeException(sprintf('There is no class definition at %s.', $definition));
        }

        $class = ClassDefinition::getByName($name);

        if (!$class instanceof ClassDefinition) {
            $class = new ClassDefinition();
            $class->setName($name);
            $class->setId($name);
            $class->setUserOwner(1);
        }

        ClassDefinition\Service::importClassDefinitionFromJson($class, $json, true);
        $class->save();

        return ClassDefinition::getByName($name)
            ?? throw new RuntimeException(sprintf('The class %s was not installed.', $name));
    }
}
