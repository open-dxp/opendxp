<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\CoreBundle\DependencyInjection\Compiler;

use OpenDxp\Twig\Sandbox\SecurityPolicy;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Sandbox\Sandbox;

final class TwigSandboxPass implements CompilerPassInterface
{
    private const array SHARED_METHOD_CALLS = [
        'addExtension',
        'addGlobal',
        'addRuntimeLoader',
        'registerUndefinedFilterCallback',
        'registerUndefinedFunctionCallback',
        'registerUndefinedTokenParserCallback',
    ];

    private const array SANDBOXES = [
        'opendxp.templating.sandbox.html' => 'html',
        'opendxp.templating.sandbox.text' => false,
    ];

    public function process(ContainerBuilder $container): void
    {
        $twig = $container->getDefinition('twig');

        foreach (self::SANDBOXES as $id => $autoescape) {
            $environment = new Definition(Environment::class, [
                $twig->getArgument(0),
                ['autoescape' => $autoescape] + $twig->getArgument(1),
            ]);

            foreach ($twig->getMethodCalls() as [$method, $arguments]) {
                if (!in_array($method, self::SHARED_METHOD_CALLS, true)) {
                    continue;
                }

                if (self::isSandboxExtension($container, $arguments)) {
                    continue;
                }

                $environment->addMethodCall($method, $arguments);
            }

            $sandbox = new Definition(Sandbox::class, [
                $environment,
                new Reference(SecurityPolicy::class),
            ]);

            $container
                ->setDefinition($id, $sandbox)
                ->setPublic(true);
        }
    }

    /**
     * The sandbox brings a sandbox extension of its own and refuses an environment that already has one.
     *
     * @param array<mixed> $arguments
     */
    private static function isSandboxExtension(ContainerBuilder $container, array $arguments): bool
    {
        $extension = $arguments[0] ?? null;

        return $extension instanceof Reference
            && $container->findDefinition((string) $extension)->getClass() === SandboxExtension::class;
    }
}
