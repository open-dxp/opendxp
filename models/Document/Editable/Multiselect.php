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

namespace OpenDxp\Model\Document\Editable;

use OpenDxp\Model;

/**
 * @method \OpenDxp\Model\Document\Editable\Dao getDao()
 */
class Multiselect extends Model\Document\Editable implements EditmodeDataInterface
{
    /**
     * Contains the current selected values
     *
     * @internal
     *
     */
    protected array $values = [];

    public function getType(): string
    {
        return 'multiselect';
    }

    public function getData(): mixed
    {
        return $this->values;
    }

    public function getValues(): array
    {
        return $this->getData();
    }

    public function frontend()
    {
        return implode(',', $this->values);
    }

    public function getDataEditmode(): array
    {
        return $this->values;
    }

    public function setDataFromResource(mixed $data): static
    {
        $unserializedData = $this->getUnserializedData($data) ?? [];
        $this->values = $unserializedData;

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
    {
        if (empty($data)) {
            $this->values = [];
        } elseif (is_string($data)) {
            $this->values = explode(',', $data);
        } elseif (is_array($data)) {
            $this->values = $data;
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->values === [];
    }
}
