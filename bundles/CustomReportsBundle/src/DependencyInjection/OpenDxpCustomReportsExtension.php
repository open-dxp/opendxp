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

namespace OpenDxp\Bundle\CustomReportsBundle\DependencyInjection;

use OpenDxp\Config\LocationAwareConfigRepository;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class OpenDxpCustomReportsExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    #[\Override]
    public function getAlias(): string
    {
        return 'opendxp_custom_reports';
    }

    private function configureAdapterFactories(ContainerBuilder $container, array $factories, string $serviceLocatorId): void
    {
        $serviceLocator = $container->getDefinition($serviceLocatorId);
        $arguments = [];

        foreach ($factories as $key => $serviceId) {
            $arguments[$key] = new Reference($serviceId);
        }

        $serviceLocator->setArgument(0, $arguments);
    }

    public function loadInternal(array $config, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );

        $loader->load('services.yaml');

        $this->configureAdapterFactories($container, $config['adapters'], 'opendxp.custom_report.adapter.factories');
        $container->setParameter('opendxp_custom_reports.definitions', $config['definitions'] ?? []);
        $container->setParameter('opendxp_custom_reports.config_location', $config['config_location'] ?? []);
    }

    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('opendxp_admin')) {
            $loader = new YamlFileLoader(
                $container,
                new FileLocator(__DIR__ . '/../../config')
            );

            $loader->load('admin-classic.yaml');
        }

        LocationAwareConfigRepository::loadSymfonyConfigFiles($container, 'opendxp_custom_reports', 'custom_reports');
    }
}
