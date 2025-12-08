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

namespace OpenDxp\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data;

use Exception;
use OpenDxp\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data;
use OpenDxp\Model\Listing\AbstractListing;

/**
 * @internal
 *
 * @method Data\Listing\Dao getDao()
 * @method Data[] load()
 * @method Data|false current()
 * @method int getTotalCount()
 */
class Listing extends AbstractListing
{
    /**
     * @return Data[]
     */
    public function getEntries(): array
    {
        return $this->getData();
    }

    /**
     * @param Data[]|null $entries
     *
     * @return $this
     */
    public function setEntries(?array $entries): static
    {
        return $this->setData($entries);
    }

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->initDao(self::class);
    }

    #[\Override]
    public function isValidOrderKey(string $key): bool
    {
        return in_array(
            $key,
            [
                'type',
                'id',
                'key',
                'index',
                'fullpath',
                'maintype',
                'subtype',
                'published',
                'creationDate',
                'modificationDate',
                'userOwner',
                'userModification',
                'data',
                'properties',
            ]
        );
    }
}
