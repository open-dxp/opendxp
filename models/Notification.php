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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Model;

use Exception;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Event\Model\NotificationEvent;
use OpenDxp\Event\NotificationEvents;
use OpenDxp\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use OpenDxp\Model\Exception\NotFoundException;
use OpenDxp\Model\Notification\Dao;

/**
 * @method Dao getDao()
 */
class Notification extends AbstractModel
{
    use RecursionBlockingEventDispatchHelperTrait;

    /**
     * @internal
     */
    protected ?int $id = null;

    /**
     * @internal
     */
    protected ?string $creationDate = null;

    /**
     * @internal
     */
    protected ?string $modificationDate = null;

    /**
     * @internal
     */
    protected ?User $sender = null;

    /**
     * @internal
     */
    protected ?User $recipient = null;

    /**
     * @internal
     */
    protected string $title = '';

    /**
     * @internal
     */
    protected ?string $type = null;

    /**
     * @internal
     */
    protected ?string $message = null;

    /**
     * @internal
     */
    protected ?string $payload = null;

    /**
     * @internal
     */
    protected ?ElementInterface $linkedElement = null;

    /**
     * @internal
     */
    protected ?string $linkedElementType = null;

    /**
     * @internal
     */
    protected bool $read = false;

    /**
     * @throws Exception
     */
    public static function getById(int $id): ?Notification
    {
        $cacheKey = sprintf('notification_%d', $id);

        try {
            $notification = RuntimeCache::get($cacheKey);
        } catch (Exception) {
            try {
                $notification = new self();
                $notification->getDao()->getById($id);
                RuntimeCache::set($cacheKey, $notification);
            } catch (NotFoundException) {
                $notification = null;
            }
        }

        return $notification;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getCreationDate(): ?string
    {
        return $this->creationDate;
    }

    /**
     * @return $this
     */
    public function setCreationDate(string $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getModificationDate(): ?string
    {
        return $this->modificationDate;
    }

    /**
     * @return $this
     */
    public function setModificationDate(string $modificationDate): static
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    /**
     * @return $this
     */
    public function setSender(?User $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    /**
     * @return $this
     */
    public function setRecipient(?User $recipient): static
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return $this
     */
    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return $this
     */
    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @return $this
     */
    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getLinkedElement(): ?ElementInterface
    {
        return $this->linkedElement;
    }

    /**
     * @return $this
     */
    public function setLinkedElement(?ElementInterface $linkedElement): static
    {
        $this->linkedElement = $linkedElement;

        $this->linkedElementType = null;

        if ($linkedElement instanceof ElementInterface) {
            $this->linkedElementType = Service::getElementType($linkedElement);
        }

        return $this;
    }

    /**
     * enum('document','asset', 'object) nullable
     */
    public function getLinkedElementType(): ?string
    {
        return $this->linkedElementType;
    }

    public function isRead(): bool
    {
        return $this->read;
    }

    /**
     * @return $this
     */
    public function setRead(bool $read): static
    {
        $this->read = $read;

        return $this;
    }

    public function getPayload(): ?string
    {
        return $this->payload;
    }

    public function setPayload(?string $payload): static
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function save(): void
    {
        $this->dispatchEvent(new NotificationEvent($this), NotificationEvents::PRE_SAVE);
        $this->getDao()->save();
        $this->dispatchEvent(new NotificationEvent($this), NotificationEvents::POST_SAVE);
    }

    /**
     * @throws Exception
     */
    public function delete(): void
    {
        $this->dispatchEvent(new NotificationEvent($this), NotificationEvents::PRE_DELETE);
        $this->getDao()->delete();
        $this->dispatchEvent(new NotificationEvent($this), NotificationEvents::POST_DELETE);
    }
}
