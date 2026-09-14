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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\CoreBundle\DependencyInjection;

use InvalidArgumentException;
use LogicException;
use OpenDxp;
use OpenDxp\Bundle\CoreBundle\EventListener\HttpCache\HttpCacheScopeListener;
use OpenDxp\Bundle\CoreBundle\EventListener\TranslationDebugListener;
use OpenDxp\Bundle\CoreBundle\HttpCache\Strategy\OpenDxpElementCacheStrategy;
use OpenDxp\Bundle\CoreBundle\HttpCache\Strategy\TranslationCacheStrategy;
use OpenDxp\Bundle\CoreBundle\HttpCache\Strategy\WebsiteSettingCacheStrategy;
use OpenDxp\Extension\Document\Areabrick\Attribute\AsAreabrick;
use OpenDxp\Http\Context\OpenDxpContextGuesser;
use OpenDxp\Loader\ImplementationLoader\ClassMapLoader;
use OpenDxp\Loader\ImplementationLoader\PrefixLoader;
use OpenDxp\Model\Document\Editable\Loader\EditableLoader;
use OpenDxp\Model\Document\Editable\Loader\PrefixLoader as DocumentEditablePrefixLoader;
use OpenDxp\Model\Factory;
use OpenDxp\Tool\SerializationScope;
use Override;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * @internal
 */
final class OpenDxpCoreExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    #[Override]
    public function getAlias(): string
    {
        return 'opendxp';
    }

    #[Override]
    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('opendxp', [
            'serialization' => [
                SerializationScope::Authentication->value => [
                    'allowed_classes' => Configuration::getBuiltInAllowedClasses(SerializationScope::Authentication),
                ],
                SerializationScope::TmpStore->value => [
                    'allowed_classes' => Configuration::getBuiltInAllowedClasses(SerializationScope::TmpStore),
                ],
            ],
        ]);
    }

    public function loadInternal(array $config, ContainerBuilder $container): void
    {
        // on container build the shutdown handler shouldn't be called
        OpenDxp::disableShutdown();

        // performance improvement, see https://github.com/symfony/symfony/pull/26276/files
        if (!$container->hasParameter('.container.dumper.inline_class_loader')) {
            $container->setParameter('.container.dumper.inline_class_loader', true);
        }

        // bundle manager/locator config
        $container->setParameter('opendxp.extensions.bundles.search_paths', $config['bundles']['search_paths']);
        $container->setParameter('opendxp.extensions.bundles.handle_composer', $config['bundles']['handle_composer']);

        if (!$container->hasParameter('opendxp.encryption.secret')) {
            $container->setParameter('opendxp.encryption.secret', $config['encryption']['secret']);
        }

        $container->setParameter(
            'opendxp.translations.admin_translation_mapping',
            $config['translations']['admin_translation_mapping']
        );

        $container->setParameter(
            'opendxp.web_profiler.toolbar.excluded_routes',
            $config['web_profiler']['toolbar']['excluded_routes']
        );

        $container->setParameter(
            'opendxp.maintenance.housekeeping.cleanup_tmp_files_atime_older_than',
            $config['maintenance']['housekeeping']['cleanup_tmp_files_atime_older_than']
        );

        $container->setParameter(
            'opendxp.maintenance.housekeeping.cleanup_profiler_files_atime_older_than',
            $config['maintenance']['housekeeping']['cleanup_profiler_files_atime_older_than']
        );

        $container->setParameter(
            'opendxp.documents.default_controller',
            $config['documents']['default_controller']
        );

        //twig security policy allowlist config
        $container->setParameter(
            'opendxp.templating.twig.sandbox_security_policy.tags',
            $config['templating_engine']['twig']['sandbox_security_policy']['tags']
        );

        $container->setParameter(
            'opendxp.templating.twig.sandbox_security_policy.filters',
            $config['templating_engine']['twig']['sandbox_security_policy']['filters']
        );

        $container->setParameter(
            'opendxp.templating.twig.sandbox_security_policy.functions',
            $config['templating_engine']['twig']['sandbox_security_policy']['functions']
        );

        // register opendxp config on container
        // TODO is this bad practice?
        // TODO only extract what we need as parameter?
        $container->setParameter('opendxp.config', $config);

        // set default domain for router to main domain if configured
        // this will be overridden from the request in web context but is handy for CLI scripts
        $domain = $config['general']['domain'] ?? '';

        if ($domain) {
            // when not an env variable, check if the domain is valid
            if (
                !str_contains($domain, 'env_') &&
                !filter_var(idn_to_ascii($domain), FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            ) {
                throw new InvalidArgumentException(sprintf('Invalid main domain name "%s"', $domain));
            }
            $container->setParameter('router.request_context.host', $config['general']['domain']);
        }

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );

        $loader->load('services.yaml');
        $loader->load('services_routing.yaml');
        $loader->load('services_workflow.yaml');
        $loader->load('extensions.yaml');
        $loader->load('request_response.yaml');
        $loader->load('l10n.yaml');
        $loader->load('argument_resolvers.yaml');
        $loader->load('class_resolvers.yaml');
        $loader->load('implementation_factories.yaml');
        $loader->load('documents.yaml');
        $loader->load('event_listeners.yaml');
        $loader->load('templating.yaml');
        $loader->load('templating_twig.yaml');
        $loader->load('profiler.yaml');
        $loader->load('migrations.yaml');
        $loader->load('aliases.yaml');
        $loader->load('image_optimizers.yaml');
        $loader->load('maintenance.yaml');
        $loader->load('commands.yaml');
        $loader->load('cache.yaml');
        $loader->load('marshaller.yaml');
        $loader->load('message_handler.yaml');
        $loader->load('class_builder.yaml');
        $loader->load('serializer.yaml');

        $this->configureHttpCache($container, $config['http_cache'] ?? []);
        $this->configureImplementationLoaders($container, $config);
        $this->configureModelFactory($container, $config);
        $this->configureClassResolvers($container, $config);
        $this->configureRouting($container, $config['routing']);
        $this->configureTranslations($container, $config['translations']);
        $this->configurePasswordHashers($container, $config);

        $container->setParameter('opendxp.workflow', $config['workflows']);

        $this->addContextRoutes($container, $config['context']);

        $container->registerAttributeForAutoconfiguration(
            AsAreabrick::class,
            static function (ChildDefinition $definition, AsAreabrick $attribute): void {
                $definition->addTag('opendxp.area.brick', ['id' => $attribute->id]);
            },
        );
    }

    private function configureModelFactory(ContainerBuilder $container, array $config): void
    {
        $service = $container->getDefinition(Factory::class);

        $classMapLoader = new Definition(ClassMapLoader::class, [$config['models']['class_overrides']]);
        $classMapLoader->setPublic(false);

        $classMapLoaderId = 'opendxp.model.factory.classmap_builder';
        $container->setDefinition($classMapLoaderId, $classMapLoader);

        $service->addMethodCall('addLoader', [new Reference($classMapLoaderId)]);
    }

    /**
     * Configure implementation loaders from config
     */
    private function configureImplementationLoaders(ContainerBuilder $container, array $config): void
    {
        $services = [
            EditableLoader::class                               => [
                'config'       => $config['documents']['editables'],
                'prefixLoader' => DocumentEditablePrefixLoader::class,
            ],
            'opendxp.implementation_loader.object.data'         => [
                'config'       => $config['objects']['class_definitions']['data'],
                'prefixLoader' => PrefixLoader::class,
            ],
            'opendxp.implementation_loader.object.layout'       => [
                'config'       => $config['objects']['class_definitions']['layout'],
                'prefixLoader' => PrefixLoader::class,
            ],
            'opendxp.implementation_loader.asset.metadata.data' => [
                'config'       => $config['assets']['metadata']['class_definitions']['data'],
                'prefixLoader' => PrefixLoader::class,
            ],
        ];

        // read config and add map/prefix loaders if configured - makes sure only needed objects are built
        // loaders are defined as private services as we don't need them outside the main type loader
        foreach ($services as $serviceId => $cfg) {
            $loaders = [];

            if ($cfg['config']['prefixes']) {
                $prefixLoader = new Definition($cfg['prefixLoader'], [$cfg['config']['prefixes']]);
                $prefixLoader->setPublic(false);

                $prefixLoaderId = $serviceId . '.prefix_loader';
                $container->setDefinition($prefixLoaderId, $prefixLoader);

                $loaders[] = new Reference($prefixLoaderId);
            }

            if ($cfg['config']['map']) {
                $classMapLoader = new Definition(ClassMapLoader::class, [$cfg['config']['map']]);
                $classMapLoader->setPublic(false);
                $classMapLoaderId = $serviceId . '.class_map_loader';
                $container->setDefinition($classMapLoaderId, $classMapLoader);
                $loaders[] = new Reference($classMapLoaderId);
            }

            $service = $container->getDefinition($serviceId);
            $service->setArguments([$loaders]);
        }
    }

    private function configureClassResolvers(ContainerBuilder $container, array $config): void
    {
        $container->setParameter('opendxp.documents.classes.map', $this->flattenConfigurationForClassResolver($config['documents']['type_definitions']));
        $container->setParameter('opendxp.assets.classes.map', $this->flattenConfigurationForClassResolver($config['assets']['type_definitions']));
    }

    private function configureRouting(ContainerBuilder $container, array $config): void
    {
        $container->setParameter(
            'opendxp.routing.static.locale_params',
            $config['static']['locale_params']
        );
    }

    private function configureTranslations(ContainerBuilder $container, array $config): void
    {
        $parameter = $config['debugging']['parameter'];

        // remove the listener as it isn't needed at all if it is disabled or the parameter is empty
        if (!$config['debugging']['enabled'] || empty($parameter)) {
            $container->removeDefinition(TranslationDebugListener::class);
        } else {
            $definition = $container->getDefinition(TranslationDebugListener::class);
            $definition->setArgument('$parameterName', $parameter);
        }
    }

    /**
     * Handle opendxp.security.password_hasher_factories mapping
     */
    private function configurePasswordHashers(ContainerBuilder $container, array $config): void
    {
        $definition = $container->findDefinition('opendxp.security.password_hasher_factory');

        $factoryMapping = [];
        foreach ($config['security']['password_hasher_factories'] as $className => $factoryConfig) {
            $factoryMapping[$className] = new Reference($factoryConfig['id']);
        }

        $definition->replaceArgument(1, $factoryMapping);
    }

    /**
     * Add context specific routes to context guesser
     */
    private function addContextRoutes(ContainerBuilder $container, array $config): void
    {
        $guesser = $container->getDefinition(OpenDxpContextGuesser::class);

        foreach ($config as $context => $contextConfig) {
            $guesser->addMethodCall('addContextRoutes', [$context, $contextConfig['routes']]);
        }
    }

    private function configureHttpCache(ContainerBuilder $container, array $config): void
    {
        $enabled = $config['enabled'] ?? false;

        $container->setParameter('opendxp.http_cache.enabled', $enabled);

        if (!$enabled) {
            return;
        }

        if (!class_exists(\FOS\HttpCacheBundle\FOSHttpCacheBundle::class)) {
            throw new LogicException(
                'opendxp.http_cache.enabled requires FOSHttpCacheBundle. Try running "composer require friendsofsymfony/http-cache-bundle" and register it in bundles.php: FOS\HttpCacheBundle\FOSHttpCacheBundle::class => [\'all\' => true].'
            );
        }

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('http_cache.yaml');

        $collectFromRequest = ($config['scope'] ?? 'controller') === 'request';
        $tagFallbackDocument = $config['tag_fallback_document'] ?? true;

        $container
            ->getDefinition(HttpCacheScopeListener::class)
            ->setArgument('$collectFromRequest', $collectFromRequest)
            ->setArgument('$tagFallbackDocument', $tagFallbackDocument);

        $this->registerHttpCacheStrategies($container, $config['elements'] ?? []);
    }

    private function registerHttpCacheStrategies(ContainerBuilder $container, array $elements): void
    {
        $documents = $elements['documents'] ?? [];
        $dataObjects = $elements['data_objects'] ?? [];
        $assets = $elements['assets'] ?? [];
        $translations = $elements['translations'] ?? [];
        $websiteSettings = $elements['website_settings'] ?? [];

        $docsEnabled = $documents['enabled'] ?? true;
        $objEnabled = $dataObjects['enabled'] ?? true;
        $assetsEnabled = $assets['enabled'] ?? true;

        if ($docsEnabled || $objEnabled || $assetsEnabled) {
            $definition = new Definition(OpenDxpElementCacheStrategy::class, [
                '$documentsEnabled'   => $docsEnabled,
                '$documentsTagList'   => $documents['tag_list'] ?? true,
                '$dataObjectsEnabled' => $objEnabled,
                '$dataObjectsTagList' => $dataObjects['tag_list'] ?? true,
                '$assetsEnabled'      => $assetsEnabled,
                '$assetsTagList'      => $assets['tag_list'] ?? true,
            ]);
            $definition->addTag('opendxp.http_cache.strategy');
            $container->setDefinition(OpenDxpElementCacheStrategy::class, $definition);
        }

        if ($translations['enabled'] ?? true) {
            $definition = new Definition(TranslationCacheStrategy::class);
            $definition->addTag('opendxp.http_cache.strategy');
            $container->setDefinition(TranslationCacheStrategy::class, $definition);
        }

        if ($websiteSettings['enabled'] ?? true) {
            $definition = new Definition(WebsiteSettingCacheStrategy::class);
            $definition->addTag('opendxp.http_cache.strategy');
            $container->setDefinition(WebsiteSettingCacheStrategy::class, $definition);
        }
    }

    /**
     * Extract class definitions and prefixes if configuration has more than just a class definition
     */
    private function flattenConfigurationForClassResolver(array $configuration): array
    {
        $newConfiguration = [];

        if (isset($configuration['map'])) {
            foreach ($configuration['map'] as $type => $config) {
                if (isset($config['class'])) {
                    $newConfiguration[$type] = $config['class'];
                }
            }
        }

        return $newConfiguration;
    }
}
