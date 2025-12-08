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

namespace OpenDxp\Model\User;

/**
 * @method \OpenDxp\Model\User\Dao getDao()
 */
class Folder extends UserRole\Folder
{
    protected string $type = 'userfolder';

    #[\Override]
    public function getChildren(): array
    {
        if ($this->children === null) {
            if ($this->getId()) {
                $list = new Listing();
                $list->setCondition('parentId = ?', $this->getId());

                $this->children = $list->getUsers();
            } else {
                $this->children = [];
            }
        }

        return $this->children;
    }
}
