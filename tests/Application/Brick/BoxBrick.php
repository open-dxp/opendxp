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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tests\Application\Brick;

use OpenDxp\Test\Document\Areablocks;
use OpenDxp\Test\Document\Brick;

final readonly class BoxBrick implements Brick
{
    /**
     * @param list<Brick> $bricks
     */
    private function __construct(private array $bricks)
    {
    }

    public static function containing(Brick ...$bricks): self
    {
        return new self(array_values($bricks));
    }

    public function id(): string
    {
        return 'box';
    }

    public function editables(): array
    {
        return Areablocks::filledWith('inside', ...$this->bricks);
    }
}
