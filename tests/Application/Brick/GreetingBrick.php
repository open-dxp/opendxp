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
