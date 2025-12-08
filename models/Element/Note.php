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

namespace OpenDxp\Model\Element;

use Exception;
use OpenDxp;
use OpenDxp\Event\Model\ModelEvent;
use OpenDxp\Event\NoteEvents;
use OpenDxp\Model;

/**
 * @method \OpenDxp\Model\Element\Note\Dao getDao()
 * @method void delete()
 */
final class Note extends Model\AbstractModel
{
    /**
     * @internal
     */
    protected ?int $id = null;

    /**
     * @internal
     */
    protected string $type;

    /**
     * @internal
     */
    protected int $cid;

    /**
     * @internal
     */
    protected string $ctype;

    /**
     * @internal
     */
    protected int $date;

    /**
     * @internal
     */
    protected ?int $user = null;

    /**
     * @internal
     */
    protected string $title = '';

    /**
     * @internal
     */
    protected string $description = '';

    /**
     * @internal
     */
    protected array $data = [];

    /**
     * If the note is locked, it can't be deleted in the admin interface
     *
     * @internal
     */
    protected bool $locked = true;

    public static function getById(int $id): ?Note
    {
        try {
            $note = new self();
            $note->getDao()->getById($id);

            return $note;
        } catch (Model\Exception\NotFoundException) {
            return null;
        }
    }

    public function addData(string $name, string $type, mixed $data): static
    {
        $this->data[$name] = [
            'type' => $type,
            'data' => $data,
        ];

        return $this;
    }

    public function setElement(ElementInterface $element): static
    {
        $this->setCid($element->getId());
        $this->setCtype(Service::getElementType($element));

        return $this;
    }

    /**
     * @throws Exception
     */
    public function save(): void
    {
        // check if there's a valid user
        // try to use the logged in user
        if (!$this->getUser() && OpenDxp::inAdmin() && $user = \OpenDxp\Tool\Admin::getCurrentUser()) {
            $this->setUser($user->getId());
        }

        $isUpdate = (bool) $this->getId();
        $this->getDao()->save();

        if (!$isUpdate) {
            OpenDxp::getEventDispatcher()->dispatch(new ModelEvent($this), NoteEvents::POST_ADD);
        }
    }

    public function setCid(int $cid): static
    {
        $this->cid = $cid;

        return $this;
    }

    public function getCid(): int
    {
        return $this->cid;
    }

    public function setCtype(string $ctype): static
    {
        $this->ctype = $ctype;

        return $this;
    }

    public function getCtype(): string
    {
        return $this->ctype;
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setDate(int $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getDate(): int
    {
        return $this->date;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setUser(int $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?int
    {
        return $this->user;
    }

    /**
     * @return $this
     */
    public function setLocked(bool $locked): static
    {
        $this->locked = $locked;

        return $this;
    }

    public function getLocked(): bool
    {
        return $this->locked;
    }
}
