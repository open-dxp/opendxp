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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Model\DataObject;

use OpenDxp\Model;
use OpenDxp\Model\Paginator\PaginateListingInterface;

/**
 * @method Model\DataObject[] load()
 * @method Model\DataObject[] getData()
 * @method Model\DataObject|false current()
 * @method int getTotalCount()
 * @method int getCount()
 * @method int[] loadIdList()
 * @method \OpenDxp\Model\DataObject\Listing\Dao getDao()
 * @method onCreateQueryBuilder(?callable $callback)
 */
class Listing extends Model\Listing\AbstractListing implements PaginateListingInterface
{
    protected bool $unpublished = false;

    protected array $objectTypes = [Model\DataObject::OBJECT_TYPE_OBJECT, Model\DataObject::OBJECT_TYPE_VARIANT, Model\DataObject::OBJECT_TYPE_FOLDER];

    /**
     * @return Model\DataObject[]
     */
    public function getObjects(): array
    {
        return $this->getData();
    }

    /**
     * @param Model\DataObject[] $objects
     *
     * @return $this
     */
    public function setObjects(array $objects): static
    {
        return $this->setData($objects);
    }

    public function getUnpublished(): bool
    {
        return $this->unpublished;
    }

    /**
     * @return $this
     */
    public function setUnpublished(bool $unpublished): static
    {
        $this->setData(null);

        $this->unpublished = $unpublished;

        return $this;
    }

    /**
     * @return $this
     */
    public function setObjectTypes(array $objectTypes): static
    {
        $this->setData(null);

        $this->objectTypes = $objectTypes;

        return $this;
    }

    public function getObjectTypes(): array
    {
        return $this->objectTypes;
    }

    public function getItems(int $offset, int $itemCountPerPage): array
    {
        $this->setOffset($offset);
        $this->setLimit($itemCountPerPage);

        return $this->load();
    }

    /**
     * @internal
     */
    public function addDistinct(): bool
    {
        return false;
    }

    /**
     * @param string $field database column to use for WHERE condition
     * @param string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
     * @param float|array|int|string $data comparison data, can be scalar or array (if operator is e.g. "IN (?)")
     *
     * @return $this
     *
     * @internal
     */
    public function addFilterByField(string $field, string $operator, float|array|int|string $data): static
    {
        if (!str_contains($operator, '?')) {
            $operator .= ' ?';
        }

        return $this->addConditionParam('`'.$field.'` '.$operator, $data);
    }
}
