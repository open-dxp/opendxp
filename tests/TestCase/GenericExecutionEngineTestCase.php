<?php

declare(strict_types=1);

namespace OpenDxp\Tests\TestCase;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use OpenDxp\TestFoundation\Container;
use OpenDxp\TestFoundation\EnvironmentTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Worker;

abstract class GenericExecutionEngineTestCase extends EnvironmentTestCase
{
    protected static function environment(): string
    {
        return 'generic_execution_engine';
    }

    protected function loadJobRun(JobRun $jobRun): JobRun
    {
        return Container::get(JobRunRepositoryInterface::class)->getJobRunById($jobRun->getId(), forceReload: true);
    }

    /**
     * Handles the messages of the engine like a worker, until none is left.
     */
    protected function consumeJobMessages(): void
    {
        $dispatcher = Container::get(EventDispatcherInterface::class);
        $stopWhenIdle = static function (WorkerRunningEvent $event): void {
            if ($event->isWorkerIdle()) {
                $event->getWorker()->stop();
            }
        };

        $transports = [];
        foreach (['opendxp_generic_execution_engine', 'job_run_retry'] as $name) {
            $transports[$name] = Container::get('messenger.transport.' . $name);
        }

        $dispatcher->addListener(WorkerRunningEvent::class, $stopWhenIdle, -100);

        try {
            $worker = new Worker($transports, Container::get('messenger.routable_message_bus'), $dispatcher);
            $worker->run(['sleep' => 0]);
        } finally {
            $dispatcher->removeListener(WorkerRunningEvent::class, $stopWhenIdle);
        }
    }
}
