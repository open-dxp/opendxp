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
class Input extends Model\Document\Editable implements EditmodeDataInterface
{
    /**
     * Contains the text for this element
     *
     * @internal
     *
     */
    protected string $text = '';

    public function getType(): string
    {
        return 'input';
    }

    public function getData(): mixed
    {
        return $this->text;
    }

    public function getText(): string
    {
        return $this->getData();
    }

    public function frontend()
    {
        $config = $this->getConfig();
        if (!isset($config['htmlspecialchars']) || $config['htmlspecialchars'] !== false) {
            return htmlspecialchars($this->text);
        }

        return $this->text;
    }

    public function getDataEditmode(): string
    {
        return htmlentities($this->text);
    }

    public function setDataFromResource(mixed $data): static
    {
        $this->text = $data;

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
    {
        $data = html_entity_decode($data, ENT_HTML5); // this is because the input is now an div contenteditable -> therefore in entities
        $this->text = $data;

        return $this;
    }

    public function isEmpty(): bool
    {
        return !(bool) strlen($this->text);
    }
}
