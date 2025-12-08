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

namespace OpenDxp\Model\DataObject\Data;

use OpenDxp\Model\DataObject\OwnerAwareFieldInterface;
use OpenDxp\Model\DataObject\Traits\OwnerAwareFieldTrait;
use OpenDxp\Model\Element\Note;

class Consent implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    protected ?Note $note = null;

    public function __construct(protected bool $consent = false, protected ?int $noteId = null)
    {
        $this->markMeDirty();
    }

    public function getConsent(): bool
    {
        return $this->consent;
    }

    public function setConsent(bool $consent): void
    {
        if ($consent !== $this->consent) {
            $this->consent = $consent;
            $this->markMeDirty();
        }
    }

    public function getNoteId(): ?int
    {
        return $this->noteId;
    }

    public function setNoteId(int $noteId): void
    {
        if ($noteId != $this->noteId) {
            $this->noteId = $noteId;
            $this->markMeDirty();
        }
    }

    public function getNote(): ?Note
    {
        if (empty($this->note) && !empty($this->noteId)) {
            $this->note = Note::getById($this->noteId);
        }

        return $this->note;
    }

    public function setNote(Note $note): void
    {
        $this->note = $note;
        $this->markMeDirty();
    }

    public function getSummaryString(): string
    {
        $note = $this->getNote();
        if ($note) {
            return $note->getTitle() . ': ' . date('r', $note->getDate());
        }

        return '';
    }
}
