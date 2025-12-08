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

use OpenDxp\Extension\Bundle\OpenDxpBundleInterface;
use OpenDxp\HttpKernel\Bundle\DependentBundleInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class Item extends AbstractItem
{
    public function __construct(
        private readonly BundleInterface $bundle,
        int $priority = 0,
        array $environments = [],
        string $source = self::SOURCE_PROGRAMATICALLY
    ) {
        parent::__construct($priority, $environments, $source);
    }

    public function getBundleIdentifier(): string
    {
        return $this->bundle::class;
    }

    public function getBundle(): BundleInterface
    {
        return $this->bundle;
    }

    public function isOpenDxpBundle(): bool
    {
        return $this->bundle instanceof OpenDxpBundleInterface;
    }

    public function registerDependencies(BundleCollection $collection): void
    {
        if ($this->bundle instanceof DependentBundleInterface) {
            $this->bundle::registerDependentBundles($collection);
        }
    }
}
