# Track Background Work

Your project runs work in the background: imports, syncs, cleanups. The engine shows such work as a job run in the
admin. Your code keeps running where it runs today. The engine only follows it.

## Rules

- A job run starts with `JobTrackingAgentInterface::trackJob()`. Nothing else starts one.
- Code in a job run gets a `JobProgress` as an argument. It reports its progress and its result through it.
- A message belongs to a job run when it implements `JobRunMessageInterface` and carries the ID of the job run. Its bus
  has the `JobTrackingMiddleware`.
- A job run ends when its callback returned and every message of the job run is done.

The agent works in every place: in a command, a service, a maintenance task, a scheduler task or a controller.

## Show the job run

```php
use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobProgress;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobTrackingAgentInterface;

final readonly class ProductImport
{
    public function __construct(
        private JobTrackingAgentInterface $jobTrackingAgent,
    ) {
    }

    public function import(): void
    {
        $this->jobTrackingAgent->trackJob('erp_product_import', function (JobProgress $progress): void {
            $this->importProducts();
        });
    }
}
```

The admin now shows the job run, its end and its errors.

- An exception in the callback fails the job run. The error log holds the message of the exception. The agent throws
  the exception on, so your code behaves as before.
- The callback can also be a method: `$this->importProducts(...)` or `[$this, 'importProducts']`.
- The admin shows the job name. It translates the name in the domain of the execution context.
- `trackJob()` takes the ID of an owner as `ownerId`. A job run without an owner belongs to the system.
- The callback runs in the process that calls `trackJob()`. In a web request, the browser waits for it. Long work runs
  in a command, a task or a worker, or it continues in messages.

## Show the progress

```php
$this->jobTrackingAgent->trackJob('erp_product_import', function (JobProgress $progress): void {
    $progress->setTotal($this->erpClient->countProducts());

    foreach ($this->erpClient->getProducts() as $product) {
        $this->importProduct($product);
        $progress->advance();
    }
});
```

The admin shows the progress as "done / total" with a bar.

| Method | Purpose |
|---|---|
| `setTotal(int $total)` | Sets the total, when your code knows it. |
| `advance(int $steps = 1)` | Moves the progress forward. |
| `setCurrentMessage(string $message, array $parameters = [])` | Sets the line that tells the user what happens now. |

- A message is a translation key or a plain text. The admin translates it into the language of the viewer.
- The `JobProgress` collects in memory and writes at most once per second.
- Without `setTotal()`, the admin shows the progress without a bar.

A user can cancel a running job run in the admin. The next call of the `JobProgress` then throws
`JobRunStoppedException`. The agent catches it. Your code stops there, and its caller does not notice the cancel. Code
without a call of the `JobProgress` runs to its end.

## Hand over files

An artifact is a file that a job run hands over as its result. The admin offers each artifact for download:

```php
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobRunArtifact;

$progress->setResult(
    'erp_product_import_finished',
    ['%count%' => $count],
    artifacts: [new JobRunArtifact('report.csv', fopen($path, 'rb'))],
);
```

- `setResult()` replaces an earlier result and its files.
- The engine keeps artifacts in the temporary storage `opendxp.temp.storage`. With several servers, this storage must
  be shared. The artifacts of a job run disappear together with the job run.

## Continue in messages

Work often continues in messages, and their handlers dispatch more messages. Each message of the job run implements
`JobRunMessageInterface` and carries the ID of the job run:

```php
use OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Messages\JobRunMessageInterface;

final readonly class ImportProductMessage implements JobRunMessageInterface
{
    public function __construct(
        public ?int $jobRunId,
        public string $productNumber,
    ) {
    }

    public function getJobRunId(): ?int
    {
        return $this->jobRunId;
    }
}
```

The callback dispatches the first messages. The ID comes from the `JobProgress`:

```php
$this->jobTrackingAgent->trackJob('erp_product_import', function (JobProgress $progress): void {
    foreach ($this->erpClient->getProductNumbers() as $productNumber) {
        $this->messageBus->dispatch(new ImportProductMessage($progress->getJobRunId(), $productNumber));
    }
});
```

Add the `JobTrackingMiddleware` to the bus of your messages:

```yaml
framework:
    messenger:
        buses:
            messenger.bus.default:
                middleware:
                    - 'OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Middleware\JobTrackingMiddleware'
```

A handler then gets the `JobProgress` of the job run as an additional argument. It passes the ID on to the messages it
dispatches:

```php
#[AsMessageHandler]
final readonly class ImportProductHandler
{
    public function __invoke(ImportProductMessage $message, ?JobProgress $progress = null): void
    {
        $this->importProduct($message->productNumber);

        foreach ($this->findMissingVariants($message->productNumber) as $variantNumber) {
            $this->messageBus->dispatch(new ImportProductMessage($progress?->getJobRunId(), $variantNumber));
        }
    }
}
```

- The job run ends after its last message, also when handlers dispatch more messages on the way.
- The admin counts the messages: "handled / dispatched". The total grows with every new message.
- After a cancel, the middleware discards the waiting messages of the job run.
- A message that Messenger tries again counts once. A message that fails for good lands in the error log.
- A message with `null` belongs to no job run. Its handler gets `null` and runs as without the engine.
- A handler that reports its own progress with `setTotal()` or `advance()` replaces the count of the messages.

> [!NOTE]
> Your bus may use `doctrine_transaction`. Put the `JobTrackingMiddleware` before it. A handler in the transaction
> dispatches the messages of the job run with `DispatchAfterCurrentBusStamp`. Messenger then sends them after the
> commit and not at all after a rollback. See "Transactional Messages" in the Symfony documentation.

## Set your own rules

An execution context groups job runs and gives them rules. Pass its name to `trackJob()`:

```php
$this->jobTrackingAgent->trackJob('erp_product_import', $callback, executionContext: 'erp');
```

```yaml
opendxp_generic_execution_engine:
    execution_context:
        erp:
            disposable: true
```

A job run of a disposable context disappears once its result is picked up. A command that a cron starts every five
minutes then keeps the admin clear. An unknown context throws `ExecutionContextNotDefinedException` at the start. See
[Configuration](../02_Configuration/02_Configuration.md).

## Limits

The count of a job run can go wrong in two cases:

- A transport throws while it sends a message. The engine counted the message already. The job run stays running.
  Set `fail_inactive_job_runs_after` to fail such a job run after a while.
- Code dispatches a message without `DispatchAfterCurrentBusStamp` in a database transaction, to a transport outside
  this transaction. The transaction rolls back. The message is out, but its count is gone. The job run then ends too
  early.
