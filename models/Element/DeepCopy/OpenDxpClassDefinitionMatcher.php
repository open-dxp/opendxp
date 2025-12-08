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

namespace OpenDxp\Model\Element\DeepCopy;

use DeepCopy\Matcher\Matcher;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Concrete;

/**
 * @internal
 */
class OpenDxpClassDefinitionMatcher implements Matcher
{
    /**
     * OpenDxpClassDefinitionMatcher constructor.
     *
     */
    public function __construct(private readonly string $matchType)
    {
    }

    /**
     * @param object $object
     * @param string $property
     *
     */
    public function matches($object, $property): bool
    {
        // TODO check if matcher only works for container type object (but not for localized fields, bricks, etc...)

        if ($object instanceof Concrete) {
            // do not call getClass on the object as this will set the class again
            $def = ClassDefinition::getById($object->getClassId());
            if ($def) {
                return $def->getFieldDefinition($property) instanceof $this->matchType;
            }
        }

        return false;
    }
}
