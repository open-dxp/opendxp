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

namespace OpenDxp\Model\Schedule;

use Exception;
use OpenDxp\Model;

/**
 * @internal
 *
 * @method \OpenDxp\Model\Schedule\Task\Dao getDao()
 * @method void save()
 */
class Task extends Model\AbstractModel
{
    protected ?int $id = null;

    protected ?int $cid = null;

    protected ?string $ctype = null;

    protected ?int $date = null;

    protected ?string $action = null;

    protected ?int $version = null;

    protected bool $active = false;

    protected ?int $userId = null;

    public static function getById(int $id): ?Task
    {
        $cacheKey = 'scheduled_task_' . $id;

        try {
            $task = \OpenDxp\Cache\RuntimeCache::get($cacheKey);
            if (!$task) {
                throw new Exception('Scheduled Task in Registry is not valid');
            }
        } catch (Exception) {
            try {
                $task = new self();
                $task->getDao()->getById($id);
                \OpenDxp\Cache\RuntimeCache::set($cacheKey, $task);
            } catch (Model\Exception\NotFoundException) {
                return null;
            }
        }

        return $task;
    }

    public static function create(array $data): Task
    {
        $task = new self();
        self::checkCreateData($data);
        $task->setValues($data);

        return $task;
    }

    public function __construct(array $data = [])
    {
        $this->setValues($data);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCid(): ?int
    {
        return $this->cid;
    }

    public function getCtype(): ?string
    {
        return $this->ctype;
    }

    public function getDate(): ?int
    {
        return $this->date;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    /**
     * @return $this
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return $this
     */
    public function setCid(?int $cid): static
    {
        $this->cid = $cid;

        return $this;
    }

    /**
     * @return $this
     */
    public function setCtype(?string $ctype): static
    {
        $this->ctype = $ctype;

        return $this;
    }

    /**
     * @return $this
     */
    public function setDate(?int $date): static
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return $this
     */
    public function setAction(?string $action): static
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return $this
     */
    public function setVersion(?int $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * @return $this
     */
    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * @return $this
     */
    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }
}
