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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Sets a opendxp.service_controllers parameter which contains all controllers registered as service
 * as an id => class mapping. Controllers are recognized if they match one of the following:
 *
 *  - are tagged with the "controller.service_arguments" DI tag
 *  - extend Symfony\Bundle\FrameworkBundle\Controller\Controller
 *  - extend Symfony\Bundle\FrameworkBundle\Controller\AbstractController
 *
 * @internal
 */
final class ServiceControllersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $serviceControllers = [];

        // find controllers tagged with controller.service_arguments first
        foreach (array_keys($container->findTaggedServiceIds('controller.service_arguments')) as $id) {
            $definition = $container->findDefinition($id);
            if ($definition->isAbstract()) {
                continue;
            }

            $serviceControllers[$id] = $definition->getClass();
        }

        // find all services extending Controller or AbstractController
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition->isAbstract()) {
                continue;
            }
            if (!$definition->getClass()) {
                continue;
            }
            if ($definition->isDeprecated()) {
                continue;
            }
            if (!$definition->isPublic()) {
                continue;
            }
            if ($definition->isPrivate()) {
                continue;
            }
            $reflector = $container->getReflectionClass($definition->getClass());
            if (!$reflector) {
                continue;
            }

            if ($reflector->isSubclassOf(AbstractController::class)) {
                $serviceControllers[$id] = $definition->getClass();
            }
        }

        ksort($serviceControllers);

        $container->setParameter('opendxp.service_controllers', $serviceControllers);
    }
}
