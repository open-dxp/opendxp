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
final class CacheNode extends Node
{
    public function __construct(
        private readonly string $key,
        private readonly ?int $ttl,
        private readonly array $tags,
        private readonly bool $force,
        Node $body,
        int $lineno,
        ?string $tag = 'opendxpcache'
    ) {
        parent::__construct(['body' => $body], [], $lineno, $tag);
    }

    #[Override]
    public function compile(Compiler $compiler): void
    {
        $splitChars = uniqid('', true);

        [$before, $after] = explode($splitChars, $this->getPhpCode($splitChars));

        $compiler
            ->addDebugInfo($this)
            ->write($before)
            ->subcompile($this->getNode('body'))
            ->write($after)
        ;
    }

    private function getPhpCode(string $splitChars): string
    {

        $key = $this->key;
        $tags = json_encode($this->tags);
        $ttl = $this->ttl ?? 'null';
        $force = json_encode($this->force);

        return <<<PHP

    \$cacheExtension = \$this->env->getExtension('OpenDxp\Twig\Extension\CacheTagExtension');
    \$key = '{$key}';
    \$tags = {$tags};
    \$ttl = {$ttl};
    \$force = {$force};
    \$content = \$cacheExtension->getContentFromCache(\$key, \$force);
    if (!\$content) {
        \$cacheExtension->startBuffering();
        {$splitChars}

        \$content = \$cacheExtension->endBuffering(\$key, \$tags, \$ttl, \$force);
    }
    echo \$content;
PHP;

    }
}
