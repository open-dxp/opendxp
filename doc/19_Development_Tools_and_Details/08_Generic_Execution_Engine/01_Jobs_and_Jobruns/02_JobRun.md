## Job Runs
Job Runs are the entity for storing information about runs of a job. There is the
[`OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface`] for all kind of CRUD operations on job runs.

### Localization of Current Message
The current message of a job run can be localized. That means, that in user interface, the message is translated into
the users' language. To ease handling of these messages, the `JobRunRepository` provides a specialized
`updateLogLocalized` method, which helps you to create a localized log entry. It writes the untranslated message to the
job run object, but translates the message to english and adds it to the job run log, based on the execution context of the Job Run.

You can pass the `message` to the method, which then again can be translated using OpenDXP's `Translations` menu by selecting `your custom` domain.
In addition, it is also possible to add a translation directly in the corresponding  `your-domain.<language>.yaml` file.

> [!NOTE]
> By default, the admin domain is used for the translation. If you want to use a different domain, you can set it in the `opendxp_generic_execution_engine` configuration.

As example see:
```php 
$this->jobRunRepository->updateLogLocalized(
    $jobRun, 'opendxp_copilot_job_execution_job_cancelled', ['%job_run_id%' => $jobRun->getId()]
);
```

## Accessing JobStep in Handler
The `JobStep` object can be accessed in the handler via the `getJobStep` method. This can be useful if you need to access the step configuration in the handler.

```php
$jobRun = $this->getJobRun($message);
$steps = $jobRun->getJob()?->getSteps();
if($steps !== null) {
    $step = $steps[$jobRun->getCurrentStep()] ?? null;
    if($step) {
        return $step->getSelectionMode();
    }
}
```

## End of a Job Run

A job run counts its open work:

- the callback of `trackJob()`, while it runs
- the dispatch of a step, while the engine dispatches its messages
- every message with `JobRunMessageInterface` and the ID of the job run, until a worker handled it or gave it up for
  good

The job run ends when its open work falls to zero. A job starts its next step at this point, and after its last step the
job run ends. A message that Messenger tries again counts once.

## Job Run States

The following states are available for a job run:

- `running` - The job run has open work.
- `finished` - The job run ended without an error.
- `finished_with_errors` - Some messages failed, at least one succeeded.
- `failed` - Every message failed, or the callback of `trackJob()` threw an exception.
- `cancelled` - A user or the code cancelled the job run.

With `stop_on_first_error`, the first error fails the job run. The `JobTrackingMiddleware` discards its other messages.

## Cancel Job Run

To cancel a job run, use the `cancelJobRun` method of the `JobExecutionAgentInterface`:

```php
$jobExecutionAgent->cancelJobRun($jobRun->getId());
```

PHP cannot stop running code from outside, so the engine cancels in three ways:

- The job run gets the state `cancelled` at once.
- The next write of the `JobProgress` throws `JobRunStoppedException`. The engine does not count it as an error. Running
  code stops at its next report, at most a second later.
- The `JobTrackingMiddleware` discards the waiting messages of the job run before their handler starts. The bus of
  the engine always has it. For your own bus, see
  [Continue in messages](03_Track_Background_Work.md#continue-in-messages).

Code without a report runs to its end. The engine drops its result.

## Rerun Job Run

To start a job again, use the `rerunJobRun` method of the `JobExecutionAgentInterface`. It starts a new job run with
the job of the given one:

```php
$jobExecutionAgent->rerunJobRun($jobRun->getId(), $ownerId);
```

The new job run repeats every step for every element with the input of the old one. Only the code that built the job
knows whether this is safe, so the admin offers no rerun.

## Disposable Job Runs

A job run in a disposable execution context disappears once its result is picked up. See
[Discard job runs](../02_Configuration/02_Configuration.md#discard-job-runs).

## Delete Job Runs

`JobExecutionAgentInterface::deleteJobRun()` deletes an ended job run with its error logs and its artifacts. It
refuses a running job run.

The maintenance deletes ended job runs after `delete_job_runs_older_than` and fails a running job run without
activity after `fail_inactive_job_runs_after`. See [Configuration](../02_Configuration/02_Configuration.md).

## Adding additional log entries

To update the log you have to inject the `JobRunExtractorInterface` and use its `logMessageToJobRun` method.
This can be useful if you want to provide additional information about why a job run has was cancelled or failed.

```php
 $this->jobRunExtractor->logMessageToJobRun(
            $jobRun,   
            'translation_key',
            [
                '%param1%' => $var1,
                '%param2%' => $var2                
            ]
);
```

## Job Run Error Logs
Job Run Error Logs are the entity for storing log information about Job Run. There is the
[`OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunErrorLogRepositoryInterface`] for all kind of CRUD operations on these logs.
