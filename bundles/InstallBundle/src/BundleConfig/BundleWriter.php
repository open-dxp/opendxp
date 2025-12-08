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

namespace OpenDxp\Bundle\InstallBundle\BundleConfig;

use OpenDxp\File;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class BundleWriter
{
    public function addBundlesToConfig(array $bundles, array $availableBundles): void
    {
        $bundlesPhpFile = OPENDXP_PROJECT_ROOT . '/config/bundles.php';

        if (!file_exists($bundlesPhpFile)) {
            throw new FileNotFoundException("File \"$bundlesPhpFile\" not found!");
        }
        $bundlesToInstall = [];
        foreach ($bundles as $bundle) {
            // check against available bundles since they can change
            if (in_array($bundle, $availableBundles)) {
                $bundlesToInstall[$bundle] = ['all' => true];
            }
        }

        // get installed bundles, they have to stay in the bundles.php, but won't be installed a second time
        $enabledBundles = include $bundlesPhpFile;

        if (is_array($enabledBundles) && $enabledBundles !== []) {
            $bundlesToInstall = [...$bundlesToInstall, ...$enabledBundles];
        }

        File::putPhpFile($bundlesPhpFile, $this->buildContents($bundlesToInstall));
    }

    private function buildContents(array $bundles): string
    {
        $contents = "<?php\n\nreturn [\n";
        foreach ($bundles as $class => $envs) {
            $contents .= "    $class::class => [";
            foreach ($envs as $env => $value) {
                $booleanValue = var_export($value, true);
                $contents .= "'$env' => $booleanValue, ";
            }
            $contents = substr($contents, 0, -2)."],\n";
        }

        return $contents . "];\n";
    }
}
