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

namespace OpenDxp\Twig\Extension\Templating\Navigation\Exception;

use LogicException;
use OpenDxp\Navigation\Renderer\RendererInterface;

class InvalidRendererException extends LogicException
{
    public static function create(string $name, mixed $renderer): static
    {
        $type = get_debug_type($renderer);

        return new static(sprintf(
            'Renderer for name "%s" was expected to implement interface "%s", "%s" given.',
            $name,
            RendererInterface::class,
            $type
        ));
    }
}
