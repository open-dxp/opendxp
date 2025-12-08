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

namespace OpenDxp\Bundle\XliffBundle\DependencyInjection;

use OpenDxp\Bundle\XliffBundle\ExportDataExtractorService\DataExtractor\DataObjectDataExtractor;
use Override;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class OpenDxpXliffExtension extends ConfigurableExtension
{
    #[Override]
    public function getAlias(): string
    {
        return 'opendxp_xliff';
    }

    protected function loadInternal(array $config, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );

        $loader->load('services.yaml');

        if (!empty($config['data_object']['translation_extractor']['attributes'])) {
            $definition = $container->getDefinition(DataObjectDataExtractor::class);
            $definition->setArgument('$exportAttributes', $config['data_object']['translation_extractor']['attributes']);
        }
    }
}
