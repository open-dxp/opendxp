<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Application\Brick;

use OpenDxp\Model\Document\Editable\Input;
use OpenDxp\Test\Document\Brick;

final readonly class GreetingBrick implements Brick
{
    private function __construct(private string $text)
    {
    }

    public static function saying(string $text): self
    {
        return new self($text);
    }

    public function id(): string
    {
        return 'greeting';
    }

    public function editables(): array
    {
        $text = new Input();
        $text->setDataFromResource($this->text);

        return ['text' => $text];
    }
}
