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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Agent;

use DateTimeImmutable;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Exception\JobRunStoppedException;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunArtifact;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunResult;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepository;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Storage\JobRunArtifactStorage;
use Symfony\Component\Clock\Clock;

final class JobProgress
{
    private ?int $total = null;

    private bool $hasReport = false;

    private int $steps = 0;

    /**
     * @var array{message: string, parameters: array<string, scalar|null>}|null
     */
    private ?array $currentMessage = null;

    private ?DateTimeImmutable $savedAt = null;

    /**
     * @internal
     */
    public function __construct(
        private readonly ?int $jobRunId,
        private readonly JobRunRepository $jobRunRepository,
        private readonly JobRunArtifactStorage $jobRunArtifactStorage,
    ) {
    }

    public function getJobRunId(): ?int
    {
        return $this->jobRunId;
    }

    /**
     * @throws JobRunStoppedException
     */
    public function setTotal(int $total): void
    {
        $this->total = $total;
        $this->hasReport = true;
        $this->saveWhenDue();
    }

    /**
     * @throws JobRunStoppedException
     */
    public function advance(int $steps = 1): void
    {
        $this->steps += $steps;
        $this->hasReport = true;
        $this->saveWhenDue();
    }

    /**
     * @param array<string, scalar|null> $parameters
     *
     * @throws JobRunStoppedException
     */
    public function setCurrentMessage(string $message, array $parameters = []): void
    {
        $this->currentMessage = ['message' => $message, 'parameters' => $parameters];
        $this->hasReport = true;
        $this->saveWhenDue();
    }

    /**
     * @param array<string, scalar|null> $parameters
     * @param list<JobRunArtifact> $artifacts
     *
     * @throws JobRunStoppedException
     */
    public function setResult(string $message, array $parameters = [], array $artifacts = []): void
    {
        if ($this->jobRunId === null) {
            return;
        }

        $this->validateState();
        $this->writeReport();

        $this->jobRunArtifactStorage->delete($this->jobRunId);
        foreach ($artifacts as $artifact) {
            $this->jobRunArtifactStorage->save($this->jobRunId, $artifact);
        }

        $this->jobRunRepository->updateResult(
            $this->jobRunId,
            new JobRunResult(
                $message,
                $parameters,
                array_map(static fn (JobRunArtifact $artifact): string => $artifact->filename, $artifacts),
            ),
        );
    }

    /**
     * @throws JobRunStoppedException
     *
     * @internal
     */
    public function save(): void
    {
        if ($this->jobRunId === null || !$this->hasReport) {
            return;
        }

        $this->validateState();
        $this->writeReport();
    }

    /**
     * @throws JobRunStoppedException
     */
    private function validateState(): void
    {
        if ($this->jobRunRepository->findState($this->jobRunId) !== JobRunStates::RUNNING) {
            throw new JobRunStoppedException(sprintf('The job run %d no longer runs.', $this->jobRunId));
        }
    }

    private function writeReport(): void
    {
        $this->savedAt = Clock::get()->now();

        if (!$this->hasReport) {
            return;
        }

        $this->jobRunRepository->updateReport($this->jobRunId, $this->total, $this->steps, $this->currentMessage);
        $this->total = null;
        $this->steps = 0;
        $this->currentMessage = null;
        $this->hasReport = false;
    }

    private function saveWhenDue(): void
    {
        if ($this->savedAt === null || Clock::get()->now() >= $this->savedAt->modify('+1 second')) {
            $this->save();
        }
    }
}
