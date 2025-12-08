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

namespace OpenDxp\HttpKernel\BundleCollection;

abstract class AbstractItem implements ItemInterface
{
    /**
     * @param string[] $environments
     */
    public function __construct(private readonly int $priority = 0, private readonly array $environments = [], private readonly string $source = self::SOURCE_PROGRAMATICALLY)
    {
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getEnvironments(): array
    {
        return $this->environments;
    }

    public function matchesEnvironment(string $environment): bool
    {
        if ($this->environments === []) {
            return true;
        }

        return in_array($environment, $this->environments, true);
    }

    public function getSource(): string
    {
        return $this->source;
    }
}
