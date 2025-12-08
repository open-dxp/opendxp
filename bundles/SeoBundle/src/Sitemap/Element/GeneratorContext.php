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

namespace OpenDxp\Bundle\SeoBundle\Sitemap\Element;

use ArrayIterator;
use Iterator;
use Presta\SitemapBundle\Service\UrlContainerInterface;

class GeneratorContext implements GeneratorContextInterface
{
    public function __construct(private readonly UrlContainerInterface $urlContainer, private readonly ?string $section = null, private array $parameters = [])
    {
    }

    public function getUrlContainer(): UrlContainerInterface
    {
        return $this->urlContainer;
    }

    public function getSection(): ?string
    {
        return $this->section;
    }

    public function all(): array
    {
        return $this->parameters;
    }

    public function keys(): array
    {
        return array_keys($this->parameters);
    }

    public function get(int|string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->parameters) ? $this->parameters[$key] : $default;
    }

    public function has(int|string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->parameters);
    }

    public function count(): int
    {
        return count($this->parameters);
    }
}
