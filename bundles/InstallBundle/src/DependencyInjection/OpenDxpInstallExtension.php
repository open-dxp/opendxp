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

namespace OpenDxp\Bundle\InstallBundle\DependencyInjection;

use OpenDxp\Bundle\InstallBundle\Installer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * @internal
 */
final class OpenDxpInstallExtension extends ConfigurableExtension
{
    #[\Override]
    public function getAlias(): string
    {
        return 'opendxp_install';
    }

    protected function loadInternal(array $config, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );

        $loader->load('services.yaml');

        $this->configureInstaller($container, $config);
    }

    private function configureInstaller(ContainerBuilder $container, array $config): void
    {
        $parameters = $config['parameters'] ?? [];
        $definition = $container->getDefinition(Installer::class);

        $dbCredentials = $parameters['database_credentials'] ?? [];
        $dbCredentials = $this->normalizeDbCredentials($dbCredentials);

        if ($dbCredentials !== []) {
            $definition->addMethodCall('setDbCredentials', [$dbCredentials]);
        }
    }

    /**
     * Only add DB credentials which are not empty
     */
    private function normalizeDbCredentials(array $dbCredentials): array
    {
        $normalized = [];
        foreach ($dbCredentials as $key => $value) {
            if (!empty($value)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
