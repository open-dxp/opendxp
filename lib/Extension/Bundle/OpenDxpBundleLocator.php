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

namespace OpenDxp\Extension\Bundle;

use OpenDxp\Composer;
use OpenDxp\Tool\ClassUtils;
use ReflectionClass;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @internal
 */
class OpenDxpBundleLocator
{
    private array $paths = [];

    public function __construct(private readonly Composer\PackageInfo $composerPackageInfo, array $paths = [], private readonly bool $handleComposer = true)
    {
        $this->setPaths($paths);
    }

    private function setPaths(array $paths): void
    {
        $fs = new Filesystem();

        foreach ($paths as $path) {
            if (!$fs->isAbsolutePath($path)) {
                $path = OPENDXP_PROJECT_ROOT . '/' . $path;
            }

            if ($fs->exists($path)) {
                $this->paths[] = $path;
            }
        }
    }

    /**
     * Locate opendxp bundles in configured paths
     *
     * @return array A list of found bundle class names
     */
    public function findBundles(): array
    {
        $result = $this->findBundlesInPaths($this->paths);
        if ($this->handleComposer) {
            $result = [...$result, ...$this->findComposerBundles()];
        }

        $result = array_values($result);
        sort($result);

        return $result;
    }

    private function findBundlesInPaths(array $paths): array
    {
        $result = [];

        $finder = new Finder();
        $finder
            ->in(array_unique(array_filter($paths, is_dir(...))))
            ->name('*Bundle.php');

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $className = ClassUtils::findClassName($file);
            if ($className) {
                $this->processBundleClass($className, $result);
            }
        }

        return $result;
    }

    /**
     * Finds composer bundles in /vendor with the following prerequisites:
     *
     *  * Composer package type is "opendxp-bundle"
     *  * If the [ extra: [ opendxp: [ bundles: [] ] ] entry is available in the config, it will use this config
     *    as list of available bundle names
     *  * If the config entry above is not available, it will scan the package directory with the same logic as for
     *    the other paths
     */
    private function findComposerBundles(): array
    {
        $openDxpBundles = $this->composerPackageInfo->getInstalledPackages('opendxp-bundle');
        $composerPaths = [];

        $result = [];
        foreach ($openDxpBundles as $packageInfo) {
            // if bundle explicitly defines bundles, use the config
            if (isset($packageInfo['extra']['opendxp'])) {
                $cfg = $packageInfo['extra']['opendxp'];
                if (isset($cfg['bundles']) && is_array($cfg['bundles'])) {
                    foreach ($cfg['bundles'] as $bundle) {
                        $this->processBundleClass($bundle, $result);
                    }
                }
            } else {
                // add path to list of composer paths which will be processed via path search
                $composerPaths[] = OPENDXP_COMPOSER_PATH . '/' . $packageInfo['name'];
            }
        }

        // wildcard process composer paths which didn't have a dedicated bundle config entry
        if (count($composerPaths) > 0) {
            return [...$result, ...$this->findBundlesInPaths($composerPaths)];
        }

        return $result;
    }

    private function processBundleClass(string $bundle, array &$result): void
    {
        if (!$bundle) {
            return;
        }

        if (!class_exists($bundle)) {
            return;
        }

        $reflector = new ReflectionClass($bundle);
        if (!$reflector->isInstantiable() || !$reflector->implementsInterface(OpenDxpBundleInterface::class)) {
            return;
        }

        $result[$reflector->getName()] = $reflector->getName();
    }
}
