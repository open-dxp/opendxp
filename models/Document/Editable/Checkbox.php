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
use Override;

/**
 * @method \OpenDxp\Model\Document\Editable\Dao getDao()
 */
class Checkbox extends Model\Document\Editable
{
    /**
     * Contains the checkbox value
     *
     * @internal
     *
     */
    protected bool $value = false;

    public function getType(): string
    {
        return 'checkbox';
    }

    public function getData(): mixed
    {
        return $this->value;
    }

    #[Override]
    public function getValue(): mixed
    {
        return $this->getData();
    }

    public function frontend()
    {
        return (string)$this->value;
    }

    public function setDataFromResource(mixed $data): static
    {
        $this->value = (bool) $data;

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
    {
        $this->value = (bool) $data;

        return $this;
    }

    public function isEmpty(): bool
    {
        return !$this->value;
    }

    public function isChecked(): bool
    {
        return $this->value;
    }
}
