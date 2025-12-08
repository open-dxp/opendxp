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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\EventSubscriber;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Utils\Traits\ThrowableChainTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;

/**
 * @internal
 */
final readonly class JobExecutionSubscriber implements EventSubscriberInterface
{
    use ThrowableChainTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => 'onWorkerMessageFailed',
            WorkerMessageHandledEvent::class => 'onWorkerMessageHandled',
        ];
    }

    public function __construct(
        private JobExecutionAgentInterface $jobExecutionAgent
    ) {
    }

    public function onWorkerMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();
        if (!$message instanceof GenericExecutionEngineMessageInterface) {
            return;
        }

        if ($event->willRetry()) {
            return;
        }

        $throwable = $this->getFirstThrowable($event->getThrowable());
        $this->jobExecutionAgent->continueJobMessageExecution($message, $throwable);
    }

    public function onWorkerMessageHandled(WorkerMessageHandledEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();
        if (!$message instanceof GenericExecutionEngineMessageInterface) {
            return;
        }

        $this->jobExecutionAgent->continueJobMessageExecution($message);
    }
}
