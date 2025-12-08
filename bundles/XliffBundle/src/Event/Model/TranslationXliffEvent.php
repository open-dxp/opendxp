<?php

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

namespace OpenDxp\Bundle\XliffBundle\Event\Model;

use OpenDxp\Bundle\XliffBundle\AttributeSet\AttributeSet;
use Symfony\Contracts\EventDispatcher\Event;

class TranslationXliffEvent extends Event
{
    public function __construct(protected AttributeSet $attributeSet)
    {
    }

    public function getAttributeSet(): AttributeSet
    {
        return $this->attributeSet;
    }
}
