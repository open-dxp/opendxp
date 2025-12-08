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

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('opendxp_seo');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode->addDefaultsIfNotSet();

        $this->addSitemapGenerators($rootNode);
        $this->addRedirectsConfig($rootNode);

        return $treeBuilder;
    }

    private function addSitemapGenerators(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('sitemaps')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('generators')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(fn ($v) => [
                                        'enabled' => true,
                                        'generator_id' => $v,
                                        'priority' => 0,
                                    ])
                                ->end()
                                ->addDefaultsIfNotSet()
                                ->canBeDisabled()
                                ->children()
                                    ->scalarNode('generator_id')
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->integerNode('priority')
                                        ->defaultValue(0)
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function addRedirectsConfig(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('redirects')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('status_codes')
                            ->info('List all redirect status codes.')
                                ->prototype('scalar')
                            ->end()
                        ->end()
                        ->booleanNode('auto_create_redirects')
                            ->info('Auto create redirects on moving documents & changing pretty url, updating Url slugs in Data Objects.')
                            ->defaultFalse()
                        ->end()
                    ->end()
                ->end();
    }
}
