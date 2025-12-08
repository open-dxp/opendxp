<?php

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
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * Adds all services with the tags "opendxp.serializer.encoder" and "opendxp.serializer.normalizer" as
 * encoders and normalizers to the opendxp.serializer service.
 *
 * This does exactly the same as the framework serializer pass, but adds encoders/normalizers to our custom opendxp
 * serializer.
 *
 * @see \Symfony\Component\Serializer\Serializer
 *
 * @internal
 */
final class SerializerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(\OpenDxp\Serializer\Serializer::class)) {
            return;
        }

        $definition = $container->getDefinition(\OpenDxp\Serializer\Serializer::class);

        // Looks for all the services tagged "serializer.normalizer" and adds them to the Serializer service
        $normalizers = $this->findAndSortTaggedServices('opendxp.serializer.normalizer', $container);

        if ($normalizers === []) {
            throw new RuntimeException('You must tag at least one service as "opendxp.serializer.normalizer" to use the OpenDxp Serializer service');
        }

        // Looks for all the services tagged "serializer.encoders" and adds them to the Serializer service
        $encoders = $this->findAndSortTaggedServices('opendxp.serializer.encoder', $container);
        if ($encoders === []) {
            throw new RuntimeException('You must tag at least one service as "opendxp.serializer.encoder" to use the OpenDxp Serializer service');
        }

        $definition->setArguments([
            '$normalizers' => $normalizers,
            '$encoders' => $encoders,
        ]);
    }
}
