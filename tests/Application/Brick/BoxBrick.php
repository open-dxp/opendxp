<?php

declare(strict_types=1);

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
