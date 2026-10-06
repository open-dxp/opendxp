<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\CoreBundle\DependencyInjection\Compiler;

use Doctrine\Migrations\Tools\Console\Command\DoctrineCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class MigrationsPrefixOptionPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach (array_keys($container->findTaggedServiceIds('console.command')) as $id) {
            $definition = $container->findDefinition($id);
            $class = $container->getParameterBag()->resolveValue($definition->getClass());

            if (is_string($class) && is_subclass_of($class, DoctrineCommand::class)) {
                $definition->addMethodCall('addOption', [
                    'prefix',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Optional prefix filter for version classes, eg. OpenDxp\Bundle\CoreBundle\Migrations',
                ]);
            }
        }
    }
}
