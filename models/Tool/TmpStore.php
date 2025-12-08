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

namespace OpenDxp\Model\Tool;

use OpenDxp\Model;

/**
 * @method bool getById(string $id)
 * @method \OpenDxp\Model\Tool\TmpStore\Dao getDao()
 */
final class TmpStore extends Model\AbstractModel
{
    /**
     * @internal
     *
     */
    protected string $id;

    /**
     * @internal
     */
    protected ?string $tag = null;

    /**
     * @internal
     *
     */
    protected mixed $data = null;

    /**
     * @internal
     *
     */
    protected int $date;

    /**
     * @internal
     *
     */
    protected int $expiryDate;

    /**
     * @internal
     *
     */
    protected bool $serialized = false;

    /**
     * @internal
     *
     */
    protected static ?self $instance = null;

    private static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private static function getDefaultLifetime(): int
    {
        return 86400 * 7;
    }

    public static function add(string $id, mixed $data, ?string $tag = null, ?int $lifetime = null): bool
    {
        $instance = self::getInstance();

        if (!$lifetime) {
            $lifetime = self::getDefaultLifetime();
        }

        if (self::get($id)) {
            return true;
        }

        return $instance->getDao()->add($id, $data, $tag, $lifetime);
    }

    public static function set(string $id, mixed $data, ?string $tag = null, ?int $lifetime = null): bool
    {
        $instance = self::getInstance();

        if (!$lifetime) {
            $lifetime = self::getDefaultLifetime();
        }

        return $instance->getDao()->add($id, $data, $tag, $lifetime);
    }

    public static function delete(string $id): void
    {
        $instance = self::getInstance();
        $instance->getDao()->delete($id);
    }

    public static function get(string $id): ?TmpStore
    {
        $item = new self();
        if ($item->getById($id)) {
            if ($item->getExpiryDate() < time()) {
                self::delete($id);
            } else {
                return $item;
            }
        }

        return null;
    }

    public static function getIdsByTag(string $tag): array
    {
        $instance = self::getInstance();

        return $instance->getDao()->getIdsByTag($tag);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(?string $tag): void
    {
        $this->tag = $tag;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function setData(mixed $data): void
    {
        $this->data = $data;
    }

    public function getDate(): int
    {
        return $this->date;
    }

    public function setDate(int $date): void
    {
        $this->date = $date;
    }

    public function isSerialized(): bool
    {
        return $this->serialized;
    }

    public function setSerialized(bool $serialized): void
    {
        $this->serialized = $serialized;
    }

    public function getExpiryDate(): int
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(int $expiryDate): void
    {
        $this->expiryDate = $expiryDate;
    }

    public function update(?int $lifetime = null): bool
    {
        if (!$lifetime) {
            $lifetime = 86400;
        }

        return $this->getDao()->add($this->getId(), $this->getData(), $this->getTag(), $lifetime);
    }
}
