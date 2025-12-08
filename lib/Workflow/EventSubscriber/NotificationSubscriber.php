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

namespace OpenDxp\Workflow\EventSubscriber;

use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use OpenDxp\Workflow;
use OpenDxp\Workflow\Notification\NotificationEmailService;
use OpenDxp\Workflow\Transition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class NotificationSubscriber implements EventSubscriberInterface
{
    const MAIL_TYPE_TEMPLATE = 'template';

    const MAIL_TYPE_DOCUMENT = 'opendxp_document';

    const NOTIFICATION_CHANNEL_MAIL = 'mail';

    const NOTIFICATION_CHANNEL_OPENDXP_NOTIFICATION = 'opendxp_notification';

    const DEFAULT_MAIL_TEMPLATE_PATH = '@OpenDxpCore/Workflow/NotificationEmail/notificationEmail.html.twig';

    protected bool $enabled = true;

    public function __construct(protected NotificationEmailService $mailService, protected Workflow\Notification\OpenDxpNotificationService $OpenDxpNotificationService, protected TranslatorInterface $translator, protected Workflow\ExpressionService $expressionService, protected Workflow\Manager $workflowManager)
    {
    }

    public function onWorkflowCompleted(Event $event): void
    {
        if (!$this->checkEvent($event)) {
            return;
        }

        /** @var ElementInterface $subject */
        $subject = $event->getSubject();
        /** @var Transition $transition */
        $transition = $event->getTransition();
        $workflow = $this->workflowManager->getWorkflowByName($event->getWorkflowName());

        if (!$workflow instanceof \Symfony\Component\Workflow\WorkflowInterface) {
            return;
        }

        $notificationSettings = $transition->getNotificationSettings();
        foreach ($notificationSettings as $notificationSetting) {
            $condition = $notificationSetting['condition'] ?? null;

            if (empty($condition) || $this->expressionService->evaluateExpression($workflow, $subject, $condition)) {
                $notifyUsers = $notificationSetting['notifyUsers'] ?? [];
                $notifyRoles = $notificationSetting['notifyRoles'] ?? [];

                if (in_array(self::NOTIFICATION_CHANNEL_MAIL, $notificationSetting['channelType'])) {
                    $this->handleNotifyPostWorkflowEmail($transition, $workflow, $subject, $notificationSetting['mailType'], $notificationSetting['mailPath'], $notifyUsers, $notifyRoles);
                }

                if (in_array(self::NOTIFICATION_CHANNEL_OPENDXP_NOTIFICATION, $notificationSetting['channelType'])) {
                    $this->handleNotifyPostWorkflowOpenDxpNotification($transition, $workflow, $subject, $notifyUsers, $notifyRoles);
                }
            }
        }
    }

    private function handleNotifyPostWorkflowEmail(Transition $transition, WorkflowInterface $workflow, ElementInterface $subject, string $mailType, string $mailPath, array $notifyUsers, array $notifyRoles): void
    {
        //notify users
        $subjectType = ($subject instanceof Concrete ? $subject->getClassName() : Service::getElementType($subject));

        $this->mailService->sendWorkflowEmailNotification(
            $notifyUsers,
            $notifyRoles,
            $workflow,
            $subjectType,
            $subject,
            $transition->getLabel(),
            $mailType,
            $mailPath
        );
    }

    private function handleNotifyPostWorkflowOpenDxpNotification(Transition $transition, WorkflowInterface $workflow, ElementInterface $subject, array $notifyUsers, array $notifyRoles): void
    {
        $subjectType = ($subject instanceof Concrete ? $subject->getClassName() : Service::getElementType($subject));
        $this->OpenDxpNotificationService->sendOpenDxpNotification(
            $notifyUsers,
            $notifyRoles,
            $workflow,
            $subjectType,
            $subject,
            $transition->getLabel()
        );
    }

    /**
     * check's if the event subscriber should be executed
     */
    private function checkEvent(Event $event): bool
    {
        return $this->isEnabled()
            && $event->getTransition() instanceof Transition
            && $event->getSubject() instanceof ElementInterface;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.completed' => ['onWorkflowCompleted', 0],
        ];
    }
}
