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

use OpenDxp\Image\Adapter\GD;
use OpenDxp\Image\Adapter\Imagick;
use OpenDxp\Image\AdapterInterface;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function extension_loaded;

/**
 * @internal
 */
final class ImageAdapterAliasPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(AdapterInterface::class)) {
            $container->getDefinition(AdapterInterface::class)->setPublic(true)->setShared(false);
        } elseif ($container->hasAlias(AdapterInterface::class)) {
            $container->getAlias(AdapterInterface::class)->setPublic(true);
        } elseif (extension_loaded('imagick')) {
            $alias = new Alias(Imagick::class, true);
            $container->setAlias(AdapterInterface::class, $alias);
        } else {
            $alias = new Alias(GD::class, true);
            $container->setAlias(AdapterInterface::class, $alias);
        }
    }
}
