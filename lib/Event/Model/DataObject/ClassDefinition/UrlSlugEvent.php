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

namespace OpenDxp\Event\Model\DataObject\ClassDefinition;

use OpenDxp\Model\DataObject\ClassDefinition\Data\UrlSlug;
use Symfony\Contracts\EventDispatcher\Event;

class UrlSlugEvent extends Event
{
    public function __construct(protected ?UrlSlug $urlSlug, protected array $data)
    {
    }

    public function getUrlSlug(): ?UrlSlug
    {
        return $this->urlSlug;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
