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

use OpenDxp\Twig\Options\BlockOptions;
use Override;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * @internal
 */
final class BlockNode extends Node
{
    public function __construct(
        private readonly string $blockName,
        private readonly BlockOptions $options,
        Node $body,
        int $lineno,
        string $tag
    ) {
        parent::__construct(['body' => $body], [], $lineno, $tag);
    }

    #[Override]
    public function compile(Compiler $compiler): void
    {
        $splitChars = uniqid('', true);

        [$part1, $part2] = explode($splitChars, $this->getPhpCode($splitChars));

        $compiler
            ->addDebugInfo($this)
            ->write($part1)
            ->subcompile($this->getNode('body'))
            ->write($part2);
    }

    private function getPhpCode(string $splitChars): string
    {
        $optionsString = $this->options->toString();

        return <<<PHP
        \$editableExtension = \$this->env->getExtension('OpenDxp\Twig\Extension\DocumentEditableExtension');
        \$block = \$editableExtension->renderEditable(\$context, 'block', '{$this->blockName}', $optionsString);
        foreach(\$block->getIterator() as \$key => \$index) {
            \$block->setCurrent(\$key);
            \$context['_block'] = \$block;
            \$config = \$block->getConfig();
            {$splitChars}
        }
PHP;

    }
}
