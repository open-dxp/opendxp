<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Application\GenericExecutionEngine;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobProgress;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobTrackingAgentInterface;

final readonly class ProductImport
{
    public function __construct(
        private JobTrackingAgentInterface $jobTrackingAgent,
    ) {
    }

    public function importWithClosure(): void
    {
        $this->jobTrackingAgent->trackJob('import', function (JobProgress $progress): void {
            $this->import($progress);
        });
    }

    public function importWithFirstClassCallable(): void
    {
        $this->jobTrackingAgent->trackJob('import', $this->importInPrivate(...));
    }

    public function importWithArray(): void
    {
        $this->jobTrackingAgent->trackJob('import', [$this, 'import']);
    }

    public function import(JobProgress $progress): void
    {
        $progress->advance();
    }

    private function importInPrivate(JobProgress $progress): void
    {
        $progress->advance();
    }
}
