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

namespace OpenDxp\Bundle\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class CacheFallbackPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('opendxp.cache.pool')) {
            $alias = new Alias('opendxp.cache.adapter.doctrine_dbal_tag_aware', true);
            $container->setAlias('opendxp.cache.pool', $alias);
        }

        // set default cache.app to OpenDxp default cache, if not configured differently
        $appCache = $container->findDefinition('cache.app');
        if ($appCache instanceof ChildDefinition && $appCache->getParent() === 'cache.adapter.filesystem') {
            $this->replaceCacheDefinition($appCache);

            foreach (array_keys($container->findTaggedServiceIds('cache.pool')) as $id) {
                $cacheDef = $container->findDefinition($id);
                if ($cacheDef instanceof ChildDefinition && $cacheDef->getParent() === 'cache.app') {
                    $this->replaceCacheDefinition($cacheDef);
                }
            }
        }
    }

    private function replaceCacheDefinition(ChildDefinition $cacheDef): void
    {
        // we need to reset the arguments, so that the change of the parent works properly
        $cacheDef->setArguments([]);
        $cacheDef->setParent('opendxp.cache.pool.app');
    }
}
