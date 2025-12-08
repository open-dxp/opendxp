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

namespace OpenDxp\Model\DataObject;

use Exception;
use OpenDxp\Model\DataObject;

/**
 * @method \OpenDxp\Model\DataObject\Folder\Dao getDao()
 */
class Folder extends DataObject
{
    protected string $type = 'folder';

    public static function create(array $values): Folder
    {
        $object = new static();
        self::checkCreateData($values);
        $object->setValues($values);

        $object->save();

        return $object;
    }

    #[\Override]
    protected function update(?bool $isUpdate = null, array $params = []): void
    {
        parent::update($isUpdate, $params);
        $this->getDao()->update($isUpdate);
    }

    #[\Override]
    public function delete(): void
    {
        if ($this->getId() == 1) {
            throw new Exception('root-node cannot be deleted');
        }

        parent::delete();
    }
}
