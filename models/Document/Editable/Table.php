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
class Table extends Model\Document\Editable
{
    /**
     * Contains the text for this element
     *
     * @internal
     *
     */
    protected array $data = [];

    public function getType(): string
    {
        return 'table';
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function frontend()
    {
        $html = '';

        if (count($this->data) > 0) {
            $html .= '<table border="0" cellpadding="0" cellspacing="0">';

            foreach ($this->data as $row) {
                $html .= '<tr>';
                foreach ($row as $col) {
                    $html .= '<td>';
                    $html .= $col;
                    $html .= '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</table>';
        }

        return $html;
    }

    public function setDataFromResource(mixed $data): static
    {
        $unserializedData = $this->getUnserializedData($data) ?? [];
        $this->data = $unserializedData;

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->data === [];
    }
}
