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

class ListingClassBuilder implements ListingClassBuilderInterface
{
    public function __construct(
        protected ListingClassFieldDefinitionBuilderInterface $fieldDefinitionBuilder
    ) {
    }

    public function buildListingClass(ClassDefinition $classDefinition): string
    {
        // create class for object list
        $extendListingClass = 'DataObject\\Listing\\Concrete';
        if ($classDefinition->getListingParentClass()) {
            $extendListingClass = $classDefinition->getListingParentClass();
            $extendListingClass = '\\'.ltrim($extendListingClass, '\\');
        }

        // create list class
        $cd = '<?php';

        $cd .= "\n\n";
        $cd .= 'namespace OpenDxp\\Model\\DataObject\\'.ucfirst($classDefinition->getName()).';';
        $cd .= "\n\n";
        $cd .= 'use OpenDxp\\Model;';
        $cd .= "\n";
        $cd .= 'use OpenDxp\\Model\\DataObject;';
        $cd .= "\n\n";
        $cd .= "/**\n";
        $cd .= ' * @method DataObject\\'.ucfirst($classDefinition->getName())."|false current()\n";
        $cd .= ' * @method DataObject\\'.ucfirst($classDefinition->getName())."[] load()\n";
        $cd .= ' * @method DataObject\\'.ucfirst($classDefinition->getName())."[] getData()\n";
        $cd .= ' * @method DataObject\\'.ucfirst($classDefinition->getName())."[] getObjects()\n";
        $cd .= ' */';
        $cd .= "\n\n";
        $cd .= 'class Listing extends '.$extendListingClass . "\n";
        $cd .= '{' . "\n";

        $cd .= ClassDefinition\Service::buildUseTraitsCode([], $classDefinition->getListingUseTraits());

        $cd .= 'protected $classId = "'. $classDefinition->getId()."\";\n";
        $cd .= 'protected $className = "'.$classDefinition->getName().'"'.";\n";

        $cd .= "\n\n";

        foreach ($classDefinition->getFieldDefinitions() as $def) {
            $cd .= $this->fieldDefinitionBuilder->buildListingClassFieldDefinition($classDefinition, $def);
        }

        $cd .= "\n\n";

        return $cd . "}\n";
    }
}
