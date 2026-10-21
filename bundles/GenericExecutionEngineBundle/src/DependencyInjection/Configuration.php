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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\DependencyInjection;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Utils\Enums\ErrorHandlingMode;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('opendxp_generic_execution_engine');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode->addDefaultsIfNotSet();

        $rootNode->children()
            ->enumNode('error_handling')
                ->values([ErrorHandlingMode::CONTINUE_ON_ERROR->value, ErrorHandlingMode::STOP_ON_FIRST_ERROR->value])
                ->info('Specifies how errors should be handled for all job run executions.')
                ->defaultValue(ErrorHandlingMode::CONTINUE_ON_ERROR->value)
            ->end()
            ->integerNode('delete_job_runs_older_than')
                ->info('Integer value in seconds. The maintenance deletes ended job runs after this time.')
                ->min(1)
                ->defaultNull()
            ->end()
            ->integerNode('fail_inactive_job_runs_after')
                ->info('Integer value in seconds. The maintenance fails a job run without activity after this time.')
                ->min(1)
                ->defaultNull()
            ->end()
            ->arrayNode('execution_context')
                ->prototype('array')
                    ->children()
                        ->scalarNode('translations_domain')
                            ->info('Translation domain which should be used by the job run. Default value is "admin".')
                            ->defaultValue('admin')
                        ->end()
                        ->enumNode('error_handling')
                            ->values(
                                [
                                    ErrorHandlingMode::CONTINUE_ON_ERROR->value,
                                    ErrorHandlingMode::STOP_ON_FIRST_ERROR->value,
                                ]
                            )
                            ->info(
                                'Error handling behavior which should be used by the job run.' .
                                ' Overrides the global value.'
                            )
                        ->end()
                        ->booleanNode('disposable')
                            ->info('A disposable job run disappears once its result is picked up.')
                            ->defaultFalse()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
