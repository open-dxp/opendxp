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

namespace OpenDxp\Workflow\Dumper;

use OpenDxp\Workflow\Transition;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\Marking;

/**
 * @internal
 */
class StateMachineGraphvizDumper extends GraphvizDumper
{
    /**
     * Dumps the workflow as a graphviz graph.
     *
     * Available options:
     *
     *  * graph: The default options for the whole graph
     *  * node: The default options for nodes (places)
     *  * edge: The default options for edges
     */
    #[\Override]
    public function dump(Definition $definition, ?Marking $marking = null, array $options = []): string
    {
        $places = $this->findPlaces($definition, $marking, $options['workflowName']);
        $edges = $this->findEdges($definition);

        $options = array_replace_recursive(self::$defaultOptions, $options);

        return $this->startDot($options)
            .$this->addPlaces($places)
            .$this->addEdges($edges)
            .$this->endDot()
        ;
    }

    /**
     * @internal
     */
    #[\Override]
    protected function findEdges(Definition $definition): array
    {
        $edges = [];

        foreach ($definition->getTransitions() as $transition) {
            foreach ($transition->getFroms() as $from) {
                foreach ($transition->getTos() as $to) {
                    $edges[$from][] = [
                        'name' => $transition->getName(),
                        'label' => $transition instanceof Transition ? $transition->getLabel() : $transition->getName(),
                        'to' => $to,
                    ];
                }
            }
        }

        return $edges;
    }

    /**
     * @internal
     */
    #[\Override]
    protected function addEdges(array $edges): string
    {
        $code = '';

        foreach ($edges as $id => $edges) {
            foreach ($edges as $edge) {
                $code .= sprintf("  place_%s -> place_%s [label=\"%s\" color=\"%s\" style=\"%s\"];\n", $this->dotize($id), $this->dotize($edge['to']), $edge['label'], '#AFAFAF', 'dashed');
            }
        }

        return $code;
    }
}
