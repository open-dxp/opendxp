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

namespace OpenDxp\Event\Model;

use OpenDxp\Event\Traits\ArgumentsAwareTrait;
use OpenDxp\Model\DataObject\AbstractObject;
use Symfony\Contracts\EventDispatcher\Event;

class DataObjectEvent extends Event implements ElementEventInterface
{
    use ArgumentsAwareTrait;

    /**
     * DataObjectEvent constructor.
     *
     */
    public function __construct(protected AbstractObject $object, array $arguments = [])
    {
        $this->arguments = $arguments;
    }

    public function getObject(): AbstractObject
    {
        return $this->object;
    }

    public function setObject(AbstractObject $object): void
    {
        $this->object = $object;
    }

    public function getElement(): AbstractObject
    {
        return $this->getObject();
    }
}
