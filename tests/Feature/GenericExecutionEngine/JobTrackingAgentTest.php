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

use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobProgress;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobTrackingAgentInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Exception\ExecutionContextNotDefinedException;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Grid\JobRunGridInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunErrorLogRepositoryInterface;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\Application\GenericExecutionEngine\BranchMessage;
use OpenDxp\Tests\Application\GenericExecutionEngine\Notes;
use OpenDxp\Tests\Application\GenericExecutionEngine\ProductImport;
use OpenDxp\Tests\Application\GenericExecutionEngine\RetryOnceMessage;
use RuntimeException;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

beforeEach(function () {
    $this->agent = Container::get(JobTrackingAgentInterface::class);
});

it('ends a job run when its code returns', function () {
    $this->agent->trackJob('import', static function (JobProgress $progress): void {
        $progress->setTotal(2);
        $progress->advance(2);
        $progress->setResult('imported');
    });

    expect(findLatestJobRun())
        ->getJobName()
        ->toBe('import')
        ->getState()
        ->toBe(JobRunStates::FINISHED)
        ->getProgress()
        ->toBe(2);
});

it('ends a job run after the last message of every branch on a bus without the middleware', function () {
    $this->agent->trackJob('sync', static function (JobProgress $progress): void {
        Container::get('job_run_test.bus')->dispatch(new BranchMessage($progress->getJobRunId(), 'root', 2));
    });

    expect(findLatestJobRun()->getState())->toBe(JobRunStates::RUNNING);

    $this->consumeJobMessages();

    expect(Container::get(Notes::class)->getAll())
        ->toHaveCount(7)
        ->each->toBe('running')
        ->and(findLatestJobRun())
        ->getState()
        ->toBe(JobRunStates::FINISHED)
        ->getSucceededMessages()
        ->toBe(7);
});

it('fails a job run when its code throws and hands the exception to the caller', function () {
    $execute = fn () => $this->agent->trackJob('import', static function (): void {
        throw new RuntimeException('The import has no source.');
    });

    expect($execute)->toThrow(RuntimeException::class, 'The import has no source.');

    $jobRun = findLatestJobRun();
    $errors = Container::get(JobRunErrorLogRepositoryInterface::class)->getLogsByJobRunId($jobRun->getId());

    expect($jobRun->getState())
        ->toBe(JobRunStates::FAILED)
        ->and($errors[0]->getErrorMessage())
        ->toStartWith('The import has no source.');
});

it('counts a message once when Messenger tries it again', function () {
    $this->agent->trackJob('sync', static function (JobProgress $progress): void {
        Container::get('generic.execution.engine.bus')->dispatch(
            new RetryOnceMessage($progress->getJobRunId(), 'retried'),
        );
    });

    $this->consumeJobMessages();

    expect(findLatestJobRun())
        ->getState()
        ->toBe(JobRunStates::FINISHED)
        ->getSucceededMessages()
        ->toBe(1)
        ->getFailedMessages()
        ->toBe(0);
});

it('counts no message that a sync transport handles at once', function () {
    $this->agent->trackJob('sync', static function (JobProgress $progress): void {
        Container::get('job_run_test.bus')->dispatch(
            new BranchMessage($progress->getJobRunId(), 'root', 0),
            [new TransportNamesStamp(['sync'])],
        );
    });

    expect(Container::get(Notes::class)->getAll())
        ->toBe(['root' => 'running'])
        ->and(findLatestJobRun())
        ->getState()
        ->toBe(JobRunStates::FINISHED);
});

it('does not wait for a message without a job run', function () {
    $this->agent->trackJob('sync', static function (): void {
        Container::get('job_run_test.bus')->dispatch(new BranchMessage(null, 'root', 0));
    });

    expect(findLatestJobRun()->getState())->toBe(JobRunStates::FINISHED);
});

it('calls the callback in each form that PHP calls', function (string $method) {
    Container::get(ProductImport::class)->{$method}();

    expect(findLatestJobRun())
        ->getState()
        ->toBe(JobRunStates::FINISHED)
        ->getProgress()
        ->toBe(1);
})->with([
    'a closure' => 'importWithClosure',
    'a first-class callable of a private method' => 'importWithFirstClassCallable',
    'an array with a public method' => 'importWithArray',
]);

it('refuses an execution context that the configuration does not define', function () {
    $track = fn () => $this->agent->trackJob('import', static fn () => null, executionContext: 'imports');

    expect($track)
        ->toThrow(ExecutionContextNotDefinedException::class)
        ->and(findLatestJobRun())
        ->toBeNull();
});

it('shows the handled and the dispatched messages as the progress of a job run without a total', function () {
    $this->agent->trackJob('sync', static function (JobProgress $progress): void {
        Container::get('job_run_test.bus')->dispatch(new BranchMessage($progress->getJobRunId(), 'root', 1));
    });
    $grid = Container::get(JobRunGridInterface::class);
    $before = $grid->convertJobRunToArray($this->loadJobRun(findLatestJobRun()));

    $this->consumeJobMessages();

    expect([$before['progress'], $before['total']])
        ->toBe([0, 1])
        ->and($grid->convertJobRunToArray(findLatestJobRun()))
        ->progress
        ->toBe(3)
        ->total
        ->toBe(3);
});

it('shows the progress of the code instead of the messages once the code reports it', function () {
    $this->agent->trackJob('sync', static function (JobProgress $progress): void {
        $progress->setTotal(10);
        $progress->advance(4);
        Container::get('job_run_test.bus')->dispatch(new BranchMessage($progress->getJobRunId(), 'root', 1));
    });

    $this->consumeJobMessages();

    expect(Container::get(JobRunGridInterface::class)->convertJobRunToArray(findLatestJobRun()))
        ->progress
        ->toBe(4)
        ->total
        ->toBe(10);
});
