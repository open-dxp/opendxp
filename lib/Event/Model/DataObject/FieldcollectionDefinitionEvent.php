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

namespace OpenDxp\Event\Model\DataObject;

use OpenDxp\Model\DataObject\Fieldcollection\Definition;
use Symfony\Contracts\EventDispatcher\Event;

class FieldcollectionDefinitionEvent extends Event
{
    public function __construct(protected Definition $fieldcollectionDefinition)
    {
    }

    public function getFieldcollectionDefinition(): Definition
    {
        return $this->fieldcollectionDefinition;
    }

    public function setFieldcollectionDefinition(Definition $fieldcollectionDefinition): void
    {
        $this->fieldcollectionDefinition = $fieldcollectionDefinition;
    }
}
