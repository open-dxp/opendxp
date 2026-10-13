# Configuration of the Generic Execution Engine

The Generic Execution Engine is configured via Symfony configuration files. Default configuration looks like this:

```yaml
opendxp_generic_execution_engine:
    error_handling: 'continue_on_error'
    execution_context:
        default:
            translations_domain: admin
```

## Define the error handling behavior
There are currently two error handling strategies available:

- `continue_on_error`: The job run continues when a message fails.
- `stop_on_first_error`: The job run fails at the first error. A worker discards its other messages.

You can define the global (used for all job runs as fallback) error handling strategy in the configuration file:
```yaml
opendxp_generic_execution_engine:
    error_handling: 'stop_on_first_error'
```

## Define the execution context
Sometimes, it's necessary to customize your translation domain or error handling strategy, depending on your bundle or specific job run.

You can achieve this by defining a custom execution context:
```yaml
opendxp_generic_execution_engine:
    execution_context:
        my_custom_context:
            translations_domain: my_custom_domain
            error_handling: 'continue_on_error'
```

You can then pass the name of your execution context when defining a new job run:
```php

$jobExecutionAgent->startJobExecution($job, $owner, 'my_custom_context');

```
Based on the execution context, the translation domain and error handling strategy are set for the job run.
By default, the `admin` translation domain is used. For error handling, the global strategy is the fallback. Its default
is `continue_on_error`. A context can disappear from the configuration while its job runs still exist. These job runs
get the defaults.

## Discard job runs

A job run of a disposable execution context disappears once its result is picked up:

```yaml
opendxp_generic_execution_engine:
    execution_context:
        my_export:
            translations_domain: admin
            disposable: true
```

| Case                              | Result                                                                    |
|-----------------------------------|---------------------------------------------------------------------------|
| Each artifact was downloaded once | The job run and its artifacts are deleted after the last download.        |
| No artifacts, with an owner       | The menu of the admin shows the end to the owner and deletes the job run. |
| No owner, ended without failure   | The job run is deleted at its end.                                        |
| Cancelled                         | The job run and its artifacts are deleted at once.                        |
| Failed                            | The artifacts are deleted at once. The job run stays with its errors.     |

The maintenance deletes an ended disposable job run one day after its end. A context keeps its job runs by default.

## Clean up job runs

The maintenance deletes ended job runs and fails running job runs without activity:

```yaml
opendxp_generic_execution_engine:
    delete_job_runs_older_than: 604800 # 7 days
    fail_inactive_job_runs_after: 86400 # 1 day
```

- `delete_job_runs_older_than` deletes an ended job run after the given number of seconds. Without it, the engine keeps
  every job run outside a disposable context.
- `fail_inactive_job_runs_after` fails a running job run when it showed no activity for the given number of seconds.
  A job run shows activity with every report and every finished message. Use a value above the longest silence of your
  code. Without it, a job run of a crashed worker stays running until a user cancels it.