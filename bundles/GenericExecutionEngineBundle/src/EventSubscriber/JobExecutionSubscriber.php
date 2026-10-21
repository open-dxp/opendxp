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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\EventSubscriber;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgent;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\PendingOperations;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Messages\JobRunMessageInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Utils\Traits\ThrowableChainTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Stamp\SentToFailureTransportStamp;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Sync\SyncTransport;

/**
 * Counts a message once, also when Messenger tries it again or sends it to the failure transport. Both go past the
 * bus.
 *
 * @internal
 */
final readonly class JobExecutionSubscriber implements EventSubscriberInterface
{
    use ThrowableChainTrait;

    public function __construct(
        private JobExecutionAgent $jobExecutionAgent,
        private PendingOperations $pendingOperations,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SendMessageToTransportsEvent::class => 'onSendMessageToTransports',
            WorkerMessageHandledEvent::class => 'onWorkerMessageHandled',
            WorkerMessageFailedEvent::class => 'onWorkerMessageFailed',
        ];
    }

    public function onSendMessageToTransports(SendMessageToTransportsEvent $event): void
    {
        $jobRunId = $this->findJobRunId($event->getEnvelope());

        // A sync transport hands the message straight to the bus. No worker reports it as done.
        $senders = array_filter(
            $event->getSenders(),
            static fn (SenderInterface $sender): bool => !$sender instanceof SyncTransport,
        );

        if ($jobRunId !== null && $senders !== []) {
            $this->pendingOperations->addMessages($jobRunId, count($senders));
        }
    }

    public function onWorkerMessageHandled(WorkerMessageHandledEvent $event): void
    {
        $jobRunId = $this->findJobRunId($event->getEnvelope());

        if ($jobRunId !== null && $this->pendingOperations->removeHandledMessage($jobRunId)) {
            $this->jobExecutionAgent->continueJobRun($jobRunId);
        }
    }

    public function onWorkerMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $envelope = $event->getEnvelope();
        $jobRunId = $this->findJobRunId($envelope);
        if ($jobRunId === null || $event->willRetry()) {
            return;
        }

        $message = $envelope->getMessage();
        $this->jobExecutionAgent->addMessageError(
            $jobRunId,
            $message instanceof GenericExecutionEngineMessageInterface ? $message->getElement()?->getId() : null,
            $this->getFirstThrowable($event->getThrowable()),
        );

        if ($this->pendingOperations->removeFailedMessage($jobRunId)) {
            $this->jobExecutionAgent->continueJobRun($jobRunId);
        }
    }

    /**
     * Returns the job run of a message. A message from the failure transport counted already at its final failure.
     */
    private function findJobRunId(Envelope $envelope): ?int
    {
        $message = $envelope->getMessage();
        if (!$message instanceof JobRunMessageInterface
            || $envelope->last(SentToFailureTransportStamp::class) !== null
        ) {
            return null;
        }

        return $message->getJobRunId();
    }
}
