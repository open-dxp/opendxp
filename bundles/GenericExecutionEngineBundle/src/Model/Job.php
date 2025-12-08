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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Model;

use InvalidArgumentException;
use OpenDxp\Model\Element\ElementDescriptor;

final class Job
{
    /**
     * @param JobStep[] $steps
     * @param ElementDescriptor[] $selectedElements
     */
    public function __construct(
        private readonly string $name,
        private readonly array $steps,
        private array $selectedElements = [],
        private readonly array $environmentData = []
    ) {
        if ($this->steps === []) {
            throw new InvalidArgumentException('Job must have at least one step');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return JobStep[]
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * @return ElementDescriptor[] $selectedElements
     */
    public function getSelectedElements(): array
    {
        return $this->selectedElements;
    }

    public function getEnvironmentData(): array
    {
        return $this->environmentData;
    }

    /**
     * @param ElementDescriptor[] $selectedElements
     */
    public function setSelectedElements(array $selectedElements): void
    {
        $this->selectedElements = $selectedElements;
    }
}
