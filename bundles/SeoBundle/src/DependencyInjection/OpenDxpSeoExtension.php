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

namespace OpenDxp\Bundle\SeoBundle\DependencyInjection;

use OpenDxp\Bundle\SeoBundle\EventListener\SitemapGeneratorListener;
use OpenDxp\DependencyInjection\ServiceCollection;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class OpenDxpSeoExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    #[\Override]
    public function getAlias(): string
    {
        return 'opendxp_seo';
    }

    public function loadInternal(array $config, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );

        $loader->load('services.yaml');
        $loader->load('maintenance.yaml');
        $loader->load('event_listeners.yaml');
        $loader->load('redirect_services.yaml');
        $loader->load('sitemap_services.yaml');
        $this->configureSitemaps($container, $config['sitemaps']);
        $container->setParameter('opendxp_seo.sitemaps', $config['sitemaps']);
        $container->setParameter('opendxp_seo.redirects', $config['redirects']);
    }

    private function configureSitemaps(ContainerBuilder $container, array $config): void
    {
        $listener = $container->getDefinition(SitemapGeneratorListener::class);

        $generators = [];
        if (isset($config['generators']) && !empty($config['generators'])) {
            $generators = $config['generators'];
        }

        uasort($generators, fn(array $a, array $b) => $b['priority'] <=> $a['priority']);

        $mapping = [];
        foreach ($generators as $generatorName => $generatorConfig) {
            if (!$generatorConfig['enabled']) {
                continue;
            }

            $mapping[$generatorName] = new Reference($generatorConfig['generator_id']);
        }

        // the locator is a symfony core service locator containing every generator
        $locator = new Definition(ServiceLocator::class, [$mapping]);
        $locator->setPublic(false);
        $locator->addTag('container.service_locator');

        // the collection decorates the locator as iterable in the defined key order
        $collection = new Definition(ServiceCollection::class, [$locator, array_keys($mapping)]);
        $collection->setPublic(false);
        $listener->setArgument('$generators', $collection);
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
    }
}
