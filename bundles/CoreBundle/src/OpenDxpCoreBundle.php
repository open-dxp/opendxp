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

namespace OpenDxp\Bundle\CoreBundle;

use OpenDxp\Bundle\AdminBundle\OpenDxpAdminBundle;
use OpenDxp\Bundle\CoreBundle\DependencyInjection\Compiler\AreabrickPass;
use OpenDxp\Bundle\CoreBundle\DependencyInjection\Compiler\CacheFallbackPass;
use OpenDxp\Bundle\CoreBundle\DependencyInjection\Compiler\HtmlSanitizerPass;
use OpenDxp\Bundle\CoreBundle\DependencyInjection\Compiler\ImageAdapterAliasPass;
use OpenDxp\Bundle\CoreBundle\DependencyInjection\Compiler\LongRunningHelperPass;
use OpenDxp\Bundle\CoreBundle\DependencyInjection\Compiler\MessageBusPublicPass;
use OpenDxp\Bundle\CoreBundle\DependencyInjection\Compiler\MonologPsrLogMessageProcessorPass;
use OpenDxp\Bundle\CoreBundle\DependencyInjection\Compiler\MonologPublicLoggerPass;
use OpenDxp\Bundle\CoreBundle\DependencyInjection\Compiler\NavigationRendererPass;
use OpenDxp\Bundle\CoreBundle\DependencyInjection\Compiler\ProfilerAliasPass;
use OpenDxp\Bundle\CoreBundle\DependencyInjection\Compiler\RegisterImageOptimizersPass;
use OpenDxp\Bundle\CoreBundle\DependencyInjection\Compiler\RegisterMaintenanceTaskPass;
use OpenDxp\Bundle\CoreBundle\DependencyInjection\Compiler\RoutingLoaderPass;
use OpenDxp\Bundle\CoreBundle\DependencyInjection\Compiler\SerializerPass;
use OpenDxp\Bundle\CoreBundle\DependencyInjection\Compiler\ServiceControllersPass;
use OpenDxp\Bundle\CoreBundle\DependencyInjection\Compiler\TranslationSanitizerPass;
use OpenDxp\Bundle\CoreBundle\DependencyInjection\Compiler\WorkflowPass;
use OpenDxp\Bundle\CoreBundle\DependencyInjection\OpenDxpCoreExtension;
use OpenDxp\HttpKernel\Bundle\DependentBundleInterface;
use OpenDxp\HttpKernel\BundleCollection\BundleCollection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @internal
 */
class OpenDxpCoreBundle extends Bundle implements DependentBundleInterface
{
    #[\Override]
    public function getContainerExtension(): ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new OpenDxpCoreExtension();
        }

        return $this->extension;
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AreabrickPass());
        $container->addCompilerPass(new NavigationRendererPass());
        $container->addCompilerPass(new ServiceControllersPass());
        $container->addCompilerPass(new MonologPublicLoggerPass());
        $container->addCompilerPass(new MonologPsrLogMessageProcessorPass());
        $container->addCompilerPass(new LongRunningHelperPass());
        $container->addCompilerPass(new WorkflowPass());
        $container->addCompilerPass(new RegisterImageOptimizersPass());
        $container->addCompilerPass(new RegisterMaintenanceTaskPass());
        $container->addCompilerPass(new RoutingLoaderPass());
        $container->addCompilerPass(new ProfilerAliasPass());
        $container->addCompilerPass(new CacheFallbackPass());
        $container->addCompilerPass(new MessageBusPublicPass());
        $container->addCompilerPass(new HtmlSanitizerPass());
        $container->addCompilerPass(new TranslationSanitizerPass());
        $container->addCompilerPass(new SerializerPass());
        $container->addCompilerPass(new ImageAdapterAliasPass());
    }

    #[\Override]
    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public static function registerDependentBundles(BundleCollection $collection): void
    {
        $collection->addBundle(new OpenDxpAdminBundle(), 60);
    }
}
