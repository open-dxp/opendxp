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
use Stringable;

class ExternalImage implements OwnerAwareFieldInterface, Stringable
{
    use OwnerAwareFieldTrait;

    public function __construct(protected ?string $url = null)
    {
        $this->markMeDirty();
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
        $this->markMeDirty();
    }

    public function __toString(): string
    {
        return (is_null($this->url)) ? '' : $this->url;
    }
}
