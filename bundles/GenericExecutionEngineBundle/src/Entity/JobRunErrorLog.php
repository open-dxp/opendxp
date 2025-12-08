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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Table;

/**
 * @internal
 */
#[Entity]
#[Table(name: 'generic_execution_engine_error_log')]
#[HasLifecycleCallbacks]
class JobRunErrorLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    public function __construct(
        #[ORM\Column(type: 'integer')]
        private int $jobRunId,
        #[ORM\Column(type: 'integer')]
        private int $stepNumber,
        #[ORM\Column(type: 'integer', nullable: true)]
        private ?int $elementId = null,
        #[ORM\Column(type: 'text', nullable: true)]
        private ?string $errorMessage = null
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getJobRunId(): int
    {
        return $this->jobRunId;
    }

    public function getElementId(): ?int
    {
        return $this->elementId;
    }

    public function getStepNumber(): ?int
    {
        return $this->stepNumber;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}
