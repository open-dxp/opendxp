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

use OpenDxp\Bundle\GenericExecutionEngineBundle\Utils\Enums\SelectionProcessingMode;

final readonly class JobStep implements JobStepInterface
{
    public function __construct(
        private string $name,
        private string $messageFQCN,
        private string $condition,
        private array $config,
        private SelectionProcessingMode $selectionProcessingMode = SelectionProcessingMode::FOR_EACH
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMessageFQCN(): string
    {
        return $this->messageFQCN;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getCondition(): string
    {
        return $this->condition;
    }

    public function getSelectionProcessingMode(): SelectionProcessingMode
    {
        return $this->selectionProcessingMode;
    }
}
