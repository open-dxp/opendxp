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

namespace OpenDxp\Tests\Feature\GenericExecutionEngine;

use Doctrine\ORM\EntityManagerInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Utils\Constants\TableConstants;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Utils\Enums\SelectionProcessingMode;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Utils\ValueObjects\LogLine;
use OpenDxp\Db;
use OpenDxp\TestFoundation\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

beforeEach(function () {
    $this->agent = Container::get(JobExecutionAgentInterface::class);
});

it('runs every step for every selected element', function () {
    $jobRun = $this->agent->startJobExecution(createJob([1, 2], [createNoteStep(), createNoteStep()]), null);

    $this->consumeJobMessages();

    expect($this->loadJobRun($jobRun))
        ->getState()
        ->toBe(JobRunStates::FINISHED)
        ->getContext()
        ->toBe(['notes' => ['0:1', '0:2', '1:1', '1:2']]);
});

it('runs a step once for all selected elements', function () {
    $job = createJob([1, 2], [createNoteStep(mode: SelectionProcessingMode::ONCE)]);
    $jobRun = $this->agent->startJobExecution($job, null);

    $this->consumeJobMessages();

    expect($this->loadJobRun($jobRun))
        ->getState()
        ->toBe(JobRunStates::FINISHED)
        ->getContext()
        ->toBe(['notes' => ['0:all']]);
});

it('skips a step whose condition is not met', function () {
    $job = createJob([1], [createNoteStep(condition: 'false'), createNoteStep()]);
    $jobRun = $this->agent->startJobExecution($job, null);

    $this->consumeJobMessages();

    expect($this->loadJobRun($jobRun))
        ->getState()
        ->toBe(JobRunStates::FINISHED)
        ->getContext()
        ->toBe(['notes' => ['1:1']]);
});

it('logs a skipped step once for all its elements', function () {
    $jobRun = $this->agent->startJobExecution(createJob([1, 2, 3], [createNoteStep(condition: 'false')]), null);

    $this->consumeJobMessages();

    $skips = array_filter(
        $this->loadJobRun($jobRun)->getLogs(),
        static fn (LogLine $line): bool => str_contains($line->getLogLine(), 'Skipping step'),
    );

    expect($skips)->toHaveCount(1);
});

it('keeps the errors of messages out of the log of the job run', function () {
    $jobRun = $this->agent->startJobExecution(createJob([1, 2], [createNoteStep(failFor: [2])]), null);

    $this->consumeJobMessages();

    $lines = array_map(static fn (LogLine $line): string => $line->getLogLine(), $this->loadJobRun($jobRun)->getLogs());

    expect(implode("\n", $lines))->not->toContain('The element 2 fails.');
});

it('counts an element once when its step condition fails only after the step was dispatched', function () {
    $steps = [
        createNoteStep(),
        createNoteStep(condition: "not ('1:1' in context['notes'])"),
        createNoteStep(),
    ];
    $jobRun = $this->agent->startJobExecution(createJob([1, 2], $steps), null);

    $this->consumeJobMessages();

    expect($this->loadJobRun($jobRun))
        ->getState()
        ->toBe(JobRunStates::FINISHED)
        ->getContext()
        ->toBe(['notes' => ['0:1', '0:2', '1:1', '2:1', '2:2']]);
});

it('finishes with errors when a step fails for some elements', function () {
    $job = createJob([1, 2], [createNoteStep(failFor: [2]), createNoteStep()]);
    $jobRun = $this->agent->startJobExecution($job, null);

    $this->consumeJobMessages();

    expect($this->loadJobRun($jobRun))
        ->getState()
        ->toBe(JobRunStates::FINISHED_WITH_ERRORS)
        ->getContext()
        ->toBe(['notes' => ['0:1', '1:1', '1:2']]);
});

it('keeps working on other job runs after a job run fails at its first error', function () {
    $job = createJob([1, 2], [createNoteStep(failFor: [1])]);
    $failing = $this->agent->startJobExecution($job, null, 'stop_on_first_error');
    $other = $this->agent->startJobExecution(createJob([3], [createNoteStep()]), null);

    $this->consumeJobMessages();

    expect($this->loadJobRun($failing))
        ->getState()
        ->toBe(JobRunStates::FAILED)
        ->getContext()
        ->toBeNull()
        ->and($this->loadJobRun($other))
        ->getState()
        ->toBe(JobRunStates::FINISHED);
});

it('discards the messages of a cancelled job run', function () {
    $jobRun = $this->agent->startJobExecution(createJob([1, 2], [createNoteStep(), createNoteStep()]), null);
    $this->agent->cancelJobRun($jobRun->getId());

    $this->consumeJobMessages();

    expect($this->loadJobRun($jobRun))
        ->getState()
        ->toBe(JobRunStates::CANCELLED)
        ->getContext()
        ->toBeNull();
});

it('fails a job run that lost its job definition', function () {
    $jobRun = $this->agent->startJobExecution(createJob([1], [createNoteStep()]), null);
    Db::get()->update(TableConstants::JOB_RUN_TABLE, ['serializedJob' => null], ['id' => $jobRun->getId()]);
    Container::get(EntityManagerInterface::class)->clear();

    $this->consumeJobMessages();

    expect($this->loadJobRun($jobRun))
        ->getState()
        ->toBe(JobRunStates::FAILED);
});

it('announces every change of the state of a job run', function () {
    $changes = [];
    Container::get(EventDispatcherInterface::class)->addListener(
        JobRunStateChangedEvent::class,
        static function (JobRunStateChangedEvent $event) use (&$changes): void {
            $changes[] = [$event->getOldState(), $event->getNewState()];
        },
    );

    $this->agent->startJobExecution(createJob([1], [createNoteStep()]), null);
    $this->consumeJobMessages();

    expect($changes)->toBe([
        ['not_started', 'running'],
        ['running', 'finished'],
    ]);
});

it('reruns a job in the execution context of its job run', function () {
    $jobRun = $this->agent->startJobExecution(createJob([1], [createNoteStep()]), null, 'stop_on_first_error');
    $this->consumeJobMessages();

    $this->agent->rerunJobRun($jobRun->getId(), null);

    expect(findLatestJobRun()->getExecutionContext())->toBe('stop_on_first_error');
});

it('ends a job run in the state that its errors call for', function (array $steps, JobRunStates $state) {
    $jobRun = $this->agent->startJobExecution(createJob([1, 2], $steps), null);

    $this->consumeJobMessages();

    expect($this->loadJobRun($jobRun))
        ->getState()
        ->toBe($state);
})->with([
    'every message of two steps fails' => [
        [createNoteStep(failFor: [1, 2]), createNoteStep(failFor: [1, 2])],
        JobRunStates::FAILED,
    ],
    'each step fails for another element' => [
        [createNoteStep(failFor: [1]), createNoteStep(failFor: [2])],
        JobRunStates::FINISHED_WITH_ERRORS,
    ],
]);
