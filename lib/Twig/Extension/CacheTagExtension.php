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

namespace OpenDxp\Twig\Extension;

use OpenDxp\Cache;
use OpenDxp\Tool;
use OpenDxp\Twig\TokenParser\CacheParser;
use Twig\Extension\AbstractExtension;
use function is_null;

/**
 * @internal
 */
class CacheTagExtension extends AbstractExtension
{
    private const string CACHE_KEY_PREFIX = 'opendxp_twigcache_';

    #[\Override]
    public function getTokenParsers(): array
    {
        return [
            new CacheParser(),
        ];
    }

    public function getContentFromCache(string $key, bool $force): string|bool
    {

        if ($this->isCacheEnabled($force)) {
            return Cache::load(self::CACHE_KEY_PREFIX . $key);
        }

        return false;
    }

    public function startBuffering(): void
    {
        ob_start();
    }

    public function endBuffering(string $key, array $tags, ?int $ttl, bool $force): string
    {
        $content = ob_get_contents();
        ob_end_clean();

        if ($this->isCacheEnabled($force)) {
            $tags[] = 'in_template';
            if (is_null($ttl)) {
                $tags[] = 'output';
            }
            $tags = array_unique($tags);
            Cache::save($content, self::CACHE_KEY_PREFIX . $key, $tags, $ttl, 996, true);
        }

        return $content;
    }

    private function isCacheEnabled(bool $force): bool
    {
        if (!Tool::isFrontendRequestByAdmin()) {
            return true;
        }
        return $force;
    }
}
