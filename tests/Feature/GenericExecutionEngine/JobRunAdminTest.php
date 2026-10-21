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
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunArtifact;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepository;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tool\Storage;
use Zenstruck\Browser\KernelBrowser;

/**
 * @param array<string, mixed> $parameters
 */
function sendJobRunRequest(User $user, string $method, string $action, array $parameters = []): KernelBrowser
{
    $url = sprintf('/admin/bundle/genericexecutionengine/job-run/%s', $action);
    $browser = Browser::actingAs($user);

    return $method === 'POST'
        ? $browser->post($url, ['body' => $parameters])
        : $browser->request($method, sprintf('%s?%s', $url, http_build_query($parameters)));
}

/**
 * @return list<int>
 */
function getListedJobRunIds(User $user, string $action): array
{
    $answer = json_decode(sendJobRunRequest($user, 'GET', $action)->content(), true, flags: JSON_THROW_ON_ERROR);

    return array_column($answer['jobRuns'], 'id');
}

beforeEach(function () {
    $this->agent = Container::get(JobExecutionAgentInterface::class);
    $this->owner = UserFactory::new()
        ->withPermissions('gee_job_run_permission')
        ->create();
    $this->stranger = UserFactory::new()
        ->withPermissions('gee_job_run_permission')
        ->create();
    $this->supervisor = UserFactory::new()
        ->withPermissions('gee_job_run_permission', 'gee_see_all_job_runs_permission')
        ->create();
    $this->jobRun = $this->agent->startJobExecution(createJob([1], [createNoteStep()]), $this->owner->getId());
    $this->systemJobRun = $this->agent->startJobExecution(createJob([2], [createNoteStep()]), null);
});

afterEach(function () {
    Storage::get('temp')->deleteDirectory('job-run');
});

it('lists the own job runs and, with the permission, every job run', function (string $user) {
    $expected = [
        'owner' => [$this->jobRun->getId()],
        'stranger' => [],
        'supervisor' => [$this->systemJobRun->getId(), $this->jobRun->getId()],
    ];

    expect(getListedJobRunIds($this->{$user}, 'list'))->toBe($expected[$user]);
})->with(['owner', 'stranger', 'supervisor']);

it('lists a job run whose execution context the configuration no longer defines', function () {
    $this->jobRun->setExecutionContext('removed_from_the_configuration');
    Container::get(JobRunRepository::class)->update($this->jobRun);

    expect(getListedJobRunIds($this->owner, 'list'))->toBe([$this->jobRun->getId()]);
});

it('offers the names of the visible jobs to the filter', function (string $user, array $names) {
    Container::get(JobTrackingAgentInterface::class)->trackJob('import', static function (): void {
    }, ownerId: $this->stranger->getId());

    $answer = json_decode(
        sendJobRunRequest($this->{$user}, 'GET', 'job-names')->content(),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect(array_column($answer['jobNames'], 'name'))->toBe($names);
})->with([
    'the owner' => ['owner', ['note-elements']],
    'the supervisor' => ['supervisor', ['import', 'note-elements']],
]);

it('refuses the overview to a user without the permission', function () {
    sendJobRunRequest(UserFactory::createOne(), 'GET', 'list')->assertStatus(403);
});

it('shows the own running job runs in the menu without a permission', function () {
    $user = UserFactory::createOne();
    $jobRun = $this->agent->startJobExecution(createJob([1], [createNoteStep()]), $user->getId());

    expect(getListedJobRunIds($user, 'menu'))->toBe([$jobRun->getId()]);
});

it('reports the end of the own job runs that the menu saw running', function () {
    $this->agent->cancelJobRun($this->jobRun->getId());
    $this->agent->cancelJobRun($this->systemJobRun->getId());

    $answer = json_decode(
        sendJobRunRequest($this->owner, 'GET', 'menu', [
            'ids' => [$this->jobRun->getId(), $this->systemJobRun->getId()],
        ])->content(),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect(array_column($answer['endedJobRuns'], 'state', 'id'))->toBe([$this->jobRun->getId() => 'cancelled']);
});

it('hides a job run from a user who may not see it', function (string $method, string $action) {
    sendJobRunRequest($this->stranger, $method, $action, ['id' => $this->jobRun->getId(), 'artifact' => 'a.csv'])
        ->assertStatus(404);
})->with([
    'a cancel' => ['POST', 'cancel'],
    'a delete' => ['DELETE', 'delete'],
    'a download' => ['GET', 'download'],
    'the errors' => ['GET', 'errors'],
]);

it('lists the errors of a job run', function () {
    $jobRun = $this->agent->startJobExecution(createJob([1, 2], [createNoteStep(failFor: [2])]), $this->owner->getId());
    $this->consumeJobMessages();

    $answer = json_decode(
        sendJobRunRequest($this->owner, 'GET', 'errors', ['id' => $jobRun->getId()])->content(),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($answer['total'])
        ->toBe(1)
        ->and($answer['errors'][0])
        ->elementId
        ->toBe(2)
        ->message
        ->toStartWith('The element 2 fails.');
});

it('refuses to delete a running job run', function () {
    sendJobRunRequest($this->owner, 'DELETE', 'delete', ['id' => $this->jobRun->getId()])->assertStatus(409);
});

it('deletes a disposable job run once its artifact is downloaded', function () {
    Container::get(JobTrackingAgentInterface::class)->trackJob(
        'export',
        static function (JobProgress $progress): void {
            $progress->setResult('exported', artifacts: [new JobRunArtifact('rows.csv', createArtifactStream('a;b'))]);
        },
        ownerId: $this->owner->getId(),
        executionContext: 'disposable',
    );
    $jobRunId = findLatestJobRun()->getId();

    $browser = sendJobRunRequest($this->owner, 'GET', 'download', ['id' => $jobRunId, 'artifact' => 'rows.csv']);

    expect($browser->content())
        ->toBe('a;b')
        ->and(Container::get(JobRunRepository::class)->findState($jobRunId))
        ->toBeNull();
});
