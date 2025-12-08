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

namespace OpenDxp\Bundle\StaticRoutesBundle\DependencyInjection;

use OpenDxp\Config\LocationAwareConfigRepository;
use Override;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class OpenDxpStaticRoutesExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    #[Override]
    public function getAlias(): string
    {
        return 'opendxp_static_routes';
    }

    public function loadInternal(array $config, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );

        $loader->load('services.yaml');

        $container->setParameter('opendxp_static_routes.definitions', $config['definitions']);
        $container->setParameter('opendxp_static_routes.config_location', $config['config_location'] ?? []);
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

        LocationAwareConfigRepository::loadSymfonyConfigFiles($container, 'opendxp_static_routes', 'staticroutes');
    }
}
