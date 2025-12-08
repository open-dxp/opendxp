<?php

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

namespace OpenDxp\Bundle\InstallBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class BundleSetupEvent extends Event
{
    private array $required = [];

    private array $excludeFromBundlesPhp = [];

    public function __construct(private array $bundles, private array $recommendations)
    {
    }

    public function getBundles(): array
    {
        return $this->bundles;
    }

    public function getRecommendedBundles(): array
    {
        return $this->recommendations;
    }

    public function addInstallableBundle(string $key, string $class, bool $recommend = false): void
    {
        $this->bundles[$key] = $class;
        if ($recommend) {
            $this->recommendations[] = $key;
        }
    }

    public function removeInstallableBundle(string $key): void
    {
        unset($this->bundles[$key]);
        if (($index = array_search($key, $this->recommendations)) !== false) {
            unset($this->recommendations[$index]);
        }
    }

    public function addRequiredBundle(string $key, string $class, bool $excludeFromBundlesPhp = false): void
    {
        $this->required[$key] = $class;
        if ($excludeFromBundlesPhp) {
            $this->excludeFromBundlesPhp[$key] = $class;
        }
    }

    public function getInstallableBundles(array $bundles): array
    {
        // merge the required bundles and make sure they are unique

        return array_unique([...array_keys($this->required), ...$bundles]);
    }

    public function getAvailableBundles(): array
    {
        return array_unique([...$this->required, ...$this->bundles]);
    }

    public function getExcludeBundlesFromPhpBundles(): array
    {
        return $this->excludeFromBundlesPhp;
    }

    /**
     * Used for the demos e.g. to skip bundle installation question
     * You can also use it to build your own bundle list and recommendations
     */
    public function clearBundlesAndRecommendations(): void
    {
        $this->bundles = [];
        $this->recommendations = [];
    }
}
