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

use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobProgress;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobTrackingAgentInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Maintenance\JobRunCleanupTask;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunArtifact;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepository;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Storage\JobRunArtifactStorage;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tool\Storage;
use RuntimeException;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Clock\NativeClock;

beforeEach(function () {
    $this->clock = new MockClock();
    Clock::set($this->clock);
    $this->agent = Container::get(JobTrackingAgentInterface::class);
    $this->repository = Container::get(JobRunRepository::class);
});

afterEach(function () {
    Clock::set(new NativeClock());
    Storage::get('temp')->deleteDirectory('job-run');
});

it('deletes a disposable job run without owner at its end', function () {
    $this->agent->trackJob('send sms', static fn () => null, executionContext: 'disposable');

    expect(findLatestJobRun())->toBeNull();
});

it('keeps a failed disposable job run with its errors and deletes its artifacts', function () {
    $execute = fn () => $this->agent->trackJob(
        'export',
        static function (JobProgress $progress): void {
            $progress->setResult('exported', artifacts: [new JobRunArtifact('half.csv', createArtifactStream('a;b'))]);

            throw new RuntimeException('The export broke off.');
        },
        executionContext: 'disposable',
    );

    expect($execute)->toThrow(RuntimeException::class);

    $jobRun = findLatestJobRun();

    expect($jobRun->getState())
        ->toBe(JobRunStates::FAILED)
        ->and(Container::get(JobRunArtifactStorage::class)->has($jobRun->getId(), 'half.csv'))
        ->toBeFalse();
});

it('deletes a disposable job run one day after its end', function () {
    $owner = UserFactory::createOne();
    $this->agent->trackJob('export', static fn () => null, ownerId: $owner->getId(), executionContext: 'disposable');
    $jobRunId = findLatestJobRun()->getId();

    $this->clock->sleep(86401);
    Container::get(JobRunCleanupTask::class)->execute();

    expect($this->repository->findState($jobRunId))->toBeNull();
});

it('deletes an ended job run after the time of the configuration', function () {
    $this->agent->trackJob('import', static fn () => null);
    $jobRunId = findLatestJobRun()->getId();

    $this->clock->sleep(604801);
    Container::get(JobRunCleanupTask::class)->execute();

    expect($this->repository->findState($jobRunId))->toBeNull();
});

it('deletes the artifacts of a job run that no longer exists', function () {
    $artifactStorage = Container::get(JobRunArtifactStorage::class);
    $artifactStorage->save(999999, new JobRunArtifact('orphan.csv', createArtifactStream('a;b')));

    Container::get(JobRunCleanupTask::class)->execute();

    expect($artifactStorage->has(999999, 'orphan.csv'))->toBeFalse();
});

it('fails a running job run without activity after the time of the configuration', function () {
    $jobRun = Container::get(JobExecutionAgentInterface::class)
        ->startJobExecution(createJob([1], [createNoteStep()]), null);

    $this->clock->sleep(3601);
    Container::get(JobRunCleanupTask::class)->execute();

    expect($this->loadJobRun($jobRun)->getState())->toBe(JobRunStates::FAILED);
});
