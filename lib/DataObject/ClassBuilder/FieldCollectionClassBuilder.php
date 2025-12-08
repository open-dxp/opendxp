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

use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Fieldcollection\Definition;

class FieldCollectionClassBuilder implements FieldCollectionClassBuilderInterface
{
    public function __construct(protected FieldDefinitionDocBlockBuilderInterface $fieldDefinitionDocBlockBuilder)
    {
    }

    public function buildClass(Definition $definition): string
    {
        $extendClass = 'DataObject\\Fieldcollection\\Data\\AbstractData';
        if ($definition->getParentClass()) {
            $extendClass = $definition->getParentClass();
            $extendClass = '\\' . ltrim($extendClass, '\\');
        }

        $infoDocBlock = '/**' . "\n";
        $infoDocBlock .= " * Fields Summary:\n";

        foreach ($definition->getFieldDefinitions() as $fieldDefinition) {
            $infoDocBlock .= ' * ' . str_replace("\n", "\n * ", trim($this->fieldDefinitionDocBlockBuilder->buildFieldDefinitionDocBlock($fieldDefinition))) . "\n";
        }

        $infoDocBlock .= ' */';

        // create class file
        $cd = '<?php';
        $cd .= "\n\n";
        $cd .= $infoDocBlock;
        $cd .= "\n\n";
        $cd .= 'namespace OpenDxp\\Model\\DataObject\\Fieldcollection\\Data;';
        $cd .= "\n\n";
        $cd .= 'use OpenDxp\\Model\\DataObject;';
        $cd .= "\n";
        $cd .= 'use OpenDxp\Model\DataObject\PreGetValueHookInterface;';
        $cd .= "\n\n";

        $implementsParts = [];

        $implements = ClassDefinition\Service::buildImplementsInterfacesCode($implementsParts, $definition->getImplementsInterfaces());

        $cd .= 'class ' . ucfirst($definition->getKey()) . ' extends ' . $extendClass . $implements . "\n";
        $cd .= '{' . "\n";

        $cd .= ClassDefinition\Service::buildFieldConstantsCode(...$definition->getFieldDefinitions());

        $cd .= 'protected string $type = "' . $definition->getKey() . "\";\n";

        foreach ($definition->getFieldDefinitions() as $key => $def) {
            $cd .= 'protected $' . $key . ";\n";
        }

        $cd .= "\n\n";

        $fdDefs = $definition->getFieldDefinitions();
        foreach ($fdDefs as $def) {
            $cd .= $def->getGetterCodeFieldcollection($definition);

            if ($def instanceof ClassDefinition\Data\Localizedfields) {
                $cd .= $def->getGetterCode($definition);
            }

            $cd .= $def->getSetterCodeFieldcollection($definition);

            if ($def instanceof ClassDefinition\Data\Localizedfields) {
                $cd .= $def->getSetterCode($definition);
            }
        }

        $cd .= "}\n";

        return $cd . "\n";
    }
}
