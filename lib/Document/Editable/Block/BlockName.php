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

namespace OpenDxp\Document\Editable\Block;

use JsonSerializable;
use OpenDxp\Model\Document\Editable;

/**
 * @internal
 *
 * Simple value object containing both name and real name of
 * a block.
 */
final readonly class BlockName implements JsonSerializable
{
    public function __construct(private string $name, private string $realName)
    {
    }

    /**
     * Factory method to create an instance from strings
     */
    public static function createFromNames(string $name, string $realName): self
    {
        return new self($name, $realName);
    }

    /**
     * Create an instance from a document editable
     */
    public static function createFromEditable(Editable $editable): self
    {
        return new self($editable->getName(), $editable->getRealName());
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRealName(): string
    {
        return $this->realName;
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'realName' => $this->realName,
        ];
    }
}
