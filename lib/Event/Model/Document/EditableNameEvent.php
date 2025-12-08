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

namespace OpenDxp\Event\Model\Document;

use OpenDxp\Document\Editable\Block\BlockState;
use OpenDxp\Model\Document;
use Symfony\Contracts\EventDispatcher\Event;

class EditableNameEvent extends Event
{
    public function __construct(
        /**
         * Editable type (e.g. "input")
         */
        private readonly string $type,
        /**
         * Editable name (e.g. "headline")
         */
        private readonly string $inputName,
        /**
         * The current block state
         */
        private readonly BlockState $blockState,
        /**
         * The built editable name
         */
        private string $editableName,
        private readonly Document $document
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getInputName(): string
    {
        return $this->inputName;
    }

    public function getBlockState(): BlockState
    {
        return $this->blockState;
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    public function getEditableName(): string
    {
        return $this->editableName;
    }

    public function setEditableName(string $editableName): void
    {
        $this->editableName = $editableName;
    }
}
