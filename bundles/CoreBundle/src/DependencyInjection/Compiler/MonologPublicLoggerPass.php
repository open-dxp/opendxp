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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class MonologPublicLoggerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $loggerPrefix = 'monolog.logger.';
        $serviceIds = array_filter($container->getServiceIds(), fn(string $id) => str_starts_with($id, $loggerPrefix));

        foreach ($serviceIds as $serviceId) {
            $container
                ->findDefinition($serviceId)
                ->setPublic(true);
        }
    }
}
