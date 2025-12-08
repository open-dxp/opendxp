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

class ClassBuilder implements ClassBuilderInterface
{
    public function __construct(
        protected FieldDefinitionDocBlockBuilderInterface $fieldDefinitionDocBlockBuilder,
        protected FieldDefinitionPropertiesBuilderInterface $propertiesBuilder,
        protected FieldDefinitionBuilderInterface $fieldDefinitionBuilder,
    ) {
    }

    public function buildClass(ClassDefinition $classDefinition): string
    {
        // create class for object
        $extendClass = 'Concrete';
        if ($classDefinition->getParentClass()) {
            $extendClass = $classDefinition->getParentClass();
            $extendClass = '\\'.ltrim($extendClass, '\\');
        }

        $cd = '<?php';
        $cd .= "\n\n";
        $cd .= '/**' . "\n";
        $cd .= ' * Inheritance: '.($classDefinition->getAllowInherit() ? 'yes' : 'no')."\n";
        $cd .= ' * Variants: '.($classDefinition->getAllowVariants() ? 'yes' : 'no')."\n";

        if ($description = $classDefinition->getDescription()) {
            $description = str_replace(
                ['/**', '*/', '//', "\n"],
                ['', '', '', "\n * "],
                $description
            );

            $cd .= ' * '.$description."\n";
        }

        $cd .= " *\n";
        $cd .= " * Fields Summary:\n";

        foreach ($classDefinition->getFieldDefinitions() as $fieldDefinition) {
            $cd .= ' * ' . str_replace("\n", "\n * ", trim($this->fieldDefinitionDocBlockBuilder->buildFieldDefinitionDocBlock($fieldDefinition))) . "\n";
        }

        $cd .= ' */';
        $cd .= "\n\n";
        $cd .= 'namespace OpenDxp\\Model\\DataObject;';
        $cd .= "\n\n";
        $cd .= 'use OpenDxp\Model\DataObject\Exception\InheritanceParentNotFoundException;';
        $cd .= "\n";
        $cd .= 'use OpenDxp\Model\DataObject\PreGetValueHookInterface;';
        $cd .= "\n\n";
        $cd .= "/**\n";
        $cd .= '* @method static \\OpenDxp\\Model\\DataObject\\'.ucfirst($classDefinition->getName()).'\Listing getList(array $config = [])'."\n";

        foreach ($classDefinition->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof ClassDefinition\Data\Localizedfields) {
                $cd .= '* @method static \\OpenDxp\\Model\\DataObject\\'.ucfirst(
                    $classDefinition->getName()
                ).'\Listing|\\OpenDxp\\Model\\DataObject\\'.ucfirst(
                    $classDefinition->getName()
                ).'|null getBy'.ucfirst(
                    $fieldDefinition->getName()
                ).'(string $field, mixed $value, ?string $locale = null, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)'."\n";

                foreach ($fieldDefinition->getFieldDefinitions() as $localizedFieldDefinition) {
                    $cd .= '* @method static \\OpenDxp\\Model\\DataObject\\'.ucfirst(
                        $classDefinition->getName()
                    ).'\Listing|\\OpenDxp\\Model\\DataObject\\'.ucfirst(
                        $classDefinition->getName()
                    ).'|null getBy'.ucfirst(
                        $localizedFieldDefinition->getName()
                    ).'(mixed $value, ?string $locale = null, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)'."\n";
                }
            } elseif ($fieldDefinition->isFilterable()) {
                $cd .= '* @method static \\OpenDxp\\Model\\DataObject\\'.ucfirst(
                    $classDefinition->getName()
                ).'\Listing|\\OpenDxp\\Model\\DataObject\\'.ucfirst(
                    $classDefinition->getName()
                ).'|null getBy'.ucfirst($fieldDefinition->getName()).'(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)'."\n";
            }
        }

        $cd .= "*/\n\n";

        $implementsParts = [];

        $implements = ClassDefinition\Service::buildImplementsInterfacesCode($implementsParts, $classDefinition->getImplementsInterfaces());

        $cd .= 'class '.ucfirst($classDefinition->getName()).' extends '.$extendClass. $implements . "\n";
        $cd .= '{' . "\n";

        $useParts = [];

        $cd .= ClassDefinition\Service::buildUseTraitsCode($useParts, $classDefinition->getUseTraits());
        $cd .= ClassDefinition\Service::buildFieldConstantsCode(...$classDefinition->getFieldDefinitions());

        $cd .= $this->propertiesBuilder->buildProperties($classDefinition);
        $cd .= "\n\n";

        $cd .= '/**'."\n";
        $cd .= '* @param array $values'."\n";
        $cd .= '* @return static'."\n";
        $cd .= '*/'."\n";
        $cd .= 'public static function create(array $values = []): static'."\n";
        $cd .= "{\n";
        $cd .= "\t".'$object = new static();'."\n";
        $cd .= "\t".'$object->setValues($values);'."\n";
        $cd .= "\t".'return $object;'."\n";
        $cd .= '}';

        $cd .= "\n\n";

        foreach ($classDefinition->getFieldDefinitions() as $def) {
            $cd .= $this->fieldDefinitionBuilder->buildFieldDefinition($classDefinition, $def);
        }

        $cd .= "}\n";

        return $cd . "\n";
    }
}
