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

use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\OwnerAwareFieldInterface;
use OpenDxp\Model\DataObject\Traits\ObjectVarTrait;
use OpenDxp\Model\DataObject\Traits\OwnerAwareFieldTrait;
use OpenDxp\Model\Element\ElementDescriptor;
use OpenDxp\Model\Element\Service;

class Video implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;
    use ObjectVarTrait;

    protected ?string $type = null;

    protected string|int|Asset|ElementDescriptor|null $data = null;

    protected string|int|Asset|ElementDescriptor|null $poster = null;

    protected ?string $title = null;

    protected ?string $description = null;

    public function setData(Asset|int|string|null $data): void
    {
        $this->data = $data;
        $this->markMeDirty();
    }

    public function getData(): Asset|int|string|null
    {
        return $this->data;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
        $this->markMeDirty();
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
        $this->markMeDirty();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setPoster(Asset|int|string|null $poster): void
    {
        $this->poster = $poster;
        $this->markMeDirty();
    }

    public function getPoster(): Asset|int|string|null
    {
        return $this->poster;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
        $this->markMeDirty();
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function __wakeup(): void
    {
        if ($this->data instanceof ElementDescriptor) {
            $asset = Service::getElementById($this->data->getType(), $this->data->getId());
            $this->data = $asset instanceof Asset ? $asset : null;
        }
        if ($this->poster instanceof ElementDescriptor) {
            $asset = Service::getElementById($this->poster->getType(), $this->poster->getId());
            $this->poster = $asset instanceof Asset ? $asset : null;
        }
    }
}
