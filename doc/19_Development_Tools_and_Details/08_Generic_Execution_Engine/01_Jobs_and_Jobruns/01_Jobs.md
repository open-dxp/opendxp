## Define Jobs
Jobs are defined and configured via `OpenDxp\Bundle\GenericExecutionEngineBundle\Model\Job` objects which contain
a name, steps (with defined message class and optional step specific configuration), selected elements and
environment data (e.g. content that is entered by a user and that should be available in job step handlers).

Create a job as follows

```php 
$job = new Job(
  'my-first-job',
  [
    new JobStep('Step 1', MyFirstTestMessage::class, '', [], StepSelectionMode::FOR_EACH),
    new JobStep('Step 2', MySecondTestMessage::class, '', [], StepSelectionMode::FOR_EACH),
  ],
  [new ElementDescriptor('object', 234)],
  [
     'foo' => 'bar'
  ]
);
```

## Executing Jobs
Execution of jobs is done via [`OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface`] service,
which provides all sorts of methods for executing and managing running jobs.

To execute a job use the `startJobExecution` method which accepts 3 arguments:
1. The job object
2. Owner ID (optional) - the ID of the user who owns the job run
3. Execution context (optional) - the context in which the job should be executed. This is used for example to get the translation domain (by default admin) for localized messages.

```php
$jobExecutionAgent->startJobExecution($job, null, 'my-custom-context');
```

:::caution
Be aware that the execution engine does not retry failed messages.

```yaml
opendxp_generic_execution_engine:
    dsn: 'doctrine://default?queue_name=opendxp_generic_execution_engine'
    retry_strategy:
        max_retries: 0 # no retries to prevent data corruption
```
:::

After starting the job execution, the job run is created and the job steps are executed based on the configuration.
For further information on how to handle job runs (cancel, stop, ...), see [Job Runs](./02_JobRun.md).

## Report from a Handler

A handler of a job step gets the `JobProgress` of its job run as an additional argument:
`__invoke(ExportMessage $message, JobProgress $progress)`. It reports progress, status line and result like any tracked
code. See [Show the progress](03_Track_Background_Work.md#show-the-progress).

## Start a Job from the Admin

An action in the admin starts its job through a route of your bundle. The route answers with the ID of the job run:

```php
#[Route('/admin/my-bundle/delete-elements', name: 'my_bundle_delete_elements', methods: ['POST'])]
public function deleteElementsAction(Request $request, JobExecutionAgentInterface $jobExecutionAgent): JsonResponse
{
    $jobRun = $jobExecutionAgent->startJobExecution(
        $this->createDeleteJob($request),
        $this->getAdminUser()->getId(),
        'my_bundle_delete',
    );

    return $this->json(['jobRunId' => $jobRun->getId()]);
}
```

The button of the action calls the runner of the engine:

```js
opendxp.bundle.genericexecutionengine.jobrun.runner.start({
    url: Routing.generate('my_bundle_delete_elements'),
    params: {ids: selectedIds.join(',')},
    title: t('delete'),
    message: t('my_bundle_delete_started')
});
```

- The runner shows the message and hands the job run to the menu in the sidebar. The menu shows its progress.
- A file of the result arrives as a download in the browser tab that started the job.
- The job run needs the user as its owner. A job run without an owner never appears in the menu.
- An execution context with `disposable: true` removes the job run once the user picked up its result. See
  [Configuration](../02_Configuration/02_Configuration.md).
