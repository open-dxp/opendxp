<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Messages;

interface JobRunMessageInterface
{
    public function getJobRunId(): ?int;
}
