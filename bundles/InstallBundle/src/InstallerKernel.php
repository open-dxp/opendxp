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

namespace OpenDxp\Bundle\InstallBundle;

use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * @internal
 */
class InstallerKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct(private string $projectRoot, string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);
    }

    #[\Override]
    public function getProjectDir(): string
    {
        return $this->projectRoot;
    }

    #[\Override]
    public function getLogDir(): string
    {
        return $this->projectRoot . '/var/installer/log';
    }

    #[\Override]
    public function getCacheDir(): string
    {
        return $this->projectRoot . '/var/installer/cache';
    }

    #[\Override]
    public function getBuildDir(): string
    {
        return $this->projectRoot . '/var/installer/build';
    }

    public function registerBundles(): array
    {
        $bundles = [
            new FrameworkBundle(),
            new MonologBundle(),
            new OpenDxpInstallBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'])) {
            $bundles[] = new DebugBundle();
        }

        return $bundles;
    }

    protected function configureContainer(ContainerConfigurator $configurator): void
    {
        $configurator->parameters()->set('secret', uniqid('installer-', true));
        $configurator->import('@OpenDxpInstallBundle/config/config.yaml');

        // load installer config files if available
        foreach (['php', 'yaml', 'yml', 'xml'] as $extension) {
            $file = sprintf('%s/config/installer.%s', $this->getProjectDir(), $extension);

            if (file_exists($file)) {
                $configurator->import($file);
            }
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        // nothing to do
    }
}
