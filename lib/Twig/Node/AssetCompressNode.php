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

namespace OpenDxp\Twig\Node;

use Override;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * @internal
 */
class AssetCompressNode extends Node
{
    public function __construct(Node $body, int $lineno, ?string $tag = 'opendxpassetcompress')
    {
        parent::__construct(['body' => $body], [], $lineno, $tag);
    }

    #[Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write("ob_start();\n")
            ->subcompile($this->getNode('body'))
            ->write("\n; echo trim(str_replace(\"\n\", '', ob_get_clean()));\n");
    }
}
