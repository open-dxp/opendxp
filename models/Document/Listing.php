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

namespace OpenDxp\Model\Document;

use OpenDxp\Model;
use OpenDxp\Model\Document;
use OpenDxp\Model\Paginator\PaginateListingInterface;

/**
 * @method Document[] load()
 * @method Document|false current()
 * @method int getTotalCount()
 * @method int getCount()
 * @method int[] loadIdList()
 * @method \OpenDxp\Model\Document\Listing\Dao getDao()
 * @method onCreateQueryBuilder(?callable $callback)
 * @method list<array<string,mixed>> loadIdPathList()
 */
class Listing extends Model\Listing\AbstractListing implements PaginateListingInterface
{
    /**
     * Return all documents as Type Document, e.g. for trees and so on there isn't the whole data required
     *
     * @internal
     *
     */
    protected bool $objectTypeDocument = false;

    /**
     * @internal
     *
     */
    protected bool $unpublished = false;

    /**
     * @return Document[]
     */
    public function getDocuments(): array
    {
        return $this->getData();
    }

    public function setDocuments(array $documents): Listing
    {
        return $this->setData($documents);
    }

    /**
     * Checks if the document is unpublished.
     *
     */
    public function getUnpublished(): bool
    {
        return $this->unpublished;
    }

    /**
     * Set the unpublished flag for the document.
     *
     *
     * @return $this
     */
    public function setUnpublished(bool $unpublished): static
    {
        $this->unpublished = $unpublished;

        return $this;
    }

    #[\Override]
    public function getCondition(): string
    {
        $condition = parent::getCondition();

        if ($condition) {
            if (Document::doHideUnpublished() && !$this->getUnpublished()) {
                $condition = ' (' . $condition . ') AND published = 1';
            }
        } elseif (Document::doHideUnpublished() && !$this->getUnpublished()) {
            $condition = 'published = 1';
        }

        return $condition;
    }

    public function getItems(int $offset, int $itemCountPerPage): array
    {
        $this->setOffset($offset);
        $this->setLimit($itemCountPerPage);

        return $this->load();
    }
}
