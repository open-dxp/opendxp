<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\GenericExecutionEngine;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunResult;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use OpenDxp\TestFoundation\Container;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

beforeEach(function () {
    Clock::set(new MockClock());
    $this->agent = Container::get(JobExecutionAgentInterface::class);
});

afterEach(function () {
    Clock::set(new NativeClock());
});

it('saves the progress of a message', function () {
    $jobRun = $this->agent->startJobExecution(createCountJob(['steps' => 3]), null);

    $this->consumeJobMessages();

    expect($this->loadJobRun($jobRun))
        ->getTotal()
        ->toBe(3)
        ->getProgress()
        ->toBe(3)
        ->getContext()
        ->toBe(['saved' => [1, 2, 3]]);
});

it('saves the progress at most once per second', function () {
    $jobRun = $this->agent->startJobExecution(createCountJob(['steps' => 3, 'secondsPerStep' => 0]), null);

    $this->consumeJobMessages();

    expect($this->loadJobRun($jobRun))
        ->getProgress()
        ->toBe(3)
        ->getContext()
        ->toBe(['saved' => [0, 0, 0]]);
});

it('stops a message at its next write after a cancel', function (int $secondsPerStep, array $saved) {
    $job = createCountJob(['steps' => 3, 'secondsPerStep' => $secondsPerStep, 'cancelAt' => 2]);
    $jobRun = $this->agent->startJobExecution($job, null);

    $this->consumeJobMessages();

    expect($this->loadJobRun($jobRun))
        ->getState()
        ->toBe(JobRunStates::CANCELLED)
        ->getContext()
        ->toBe(['saved' => $saved])
        ->getResult()
        ->toBeNull();
})->with([
    'a slow message' => [1, [1]],
    'a fast message' => [0, [0, 0, 0]],
]);

it('does not count a message as failed when its job run stops it', function () {
    $failures = 0;
    Container::get(EventDispatcherInterface::class)->addListener(
        WorkerMessageFailedEvent::class,
        static function () use (&$failures): void {
            $failures++;
        },
    );
    $this->agent->startJobExecution(createCountJob(['steps' => 3, 'cancelAt' => 2]), null);

    $this->consumeJobMessages();

    expect($failures)->toBe(0);
});

it('hands the result of a message to the job run', function () {
    $jobRun = $this->agent->startJobExecution(createCountJob(['steps' => 1]), null);

    $this->consumeJobMessages();

    expect($this->loadJobRun($jobRun))
        ->getResult()
        ->toEqual(new JobRunResult('Counted every step.'));
});
