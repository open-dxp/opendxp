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

namespace OpenDxp\Model\Metadata\Predefined;

use Exception;
use OpenDxp\Model\AbstractModel;
use OpenDxp\Model\Listing\CallableFilterListingInterface;
use OpenDxp\Model\Listing\CallableOrderListingInterface;
use OpenDxp\Model\Listing\Traits\FilterListingTrait;
use OpenDxp\Model\Listing\Traits\OrderListingTrait;

/**
 * @internal
 *
 * @method \OpenDxp\Model\Metadata\Predefined\Listing\Dao getDao()
 * @method int getTotalCount()
 */
class Listing extends AbstractModel implements CallableFilterListingInterface, CallableOrderListingInterface
{
    use FilterListingTrait;
    use OrderListingTrait;

    /**
     * @var \OpenDxp\Model\Metadata\Predefined[]|null
     */
    protected ?array $definitions = null;

    /**
     * @return \OpenDxp\Model\Metadata\Predefined[]
     */
    public function getDefinitions(): array
    {
        if ($this->definitions === null) {
            $this->getDao()->loadList();
        }

        return $this->definitions;
    }

    /**
     * @param \OpenDxp\Model\Metadata\Predefined[]|null $definitions
     *
     * @return $this
     */
    public function setDefinitions(?array $definitions): static
    {
        $this->definitions = $definitions;

        return $this;
    }

    /**
     *
     * @return \OpenDxp\Model\Metadata\Predefined[]|null
     *
     * @throws Exception
     */
    public static function getByTargetType(string $type, array|string|null $subTypes = null): ?array
    {
        if ($type !== 'asset') {
            throw new Exception('other types than assets are currently not supported');
        }

        $list = new self();

        if ($subTypes && !is_array($subTypes)) {
            $subTypes = [$subTypes];
        }

        if (is_array($subTypes)) {
            return array_filter($list->load(), function ($item) use ($subTypes) {
                if (empty($item->getTargetSubtype())) {
                    return true;
                }

                return in_array($item->getTargetSubtype(), $subTypes);
            });
        }

        return $list->load();
    }

    public static function getByKeyAndLanguage(string $key, ?string $language, ?string $targetSubtype = null): ?\OpenDxp\Model\Metadata\Predefined
    {
        $list = new self();

        foreach ($list->load() as $item) {
            if ($item->getName() != $key) {
                continue;
            }

            if ($language && $language != $item->getLanguage()) {
                continue;
            }

            if ($targetSubtype && $targetSubtype != $item->getTargetSubtype()) {
                continue;
            }

            return $item;
        }

        return null;
    }

    /**
     * @return \OpenDxp\Model\Metadata\Predefined[]
     */
    public function load(): array
    {
        return $this->getDefinitions();
    }
}
