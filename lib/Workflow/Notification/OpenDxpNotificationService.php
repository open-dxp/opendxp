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

namespace OpenDxp\Workflow\Notification;

use Exception;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Notification\Service\NotificationService;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class OpenDxpNotificationService extends AbstractNotificationService
{
    /**
     * OpenDxpNotificationService constructor.
     *
     */
    public function __construct(protected NotificationService $notificationService, protected TranslatorInterface $translator)
    {
    }

    public function sendOpenDxpNotification(array $users, array $roles, WorkflowInterface $workflow, string $subjectType, ElementInterface $subject, string $action): void
    {
        try {
            $recipients = $this->getNotificationUsersByName($users, $roles, true);
            if (!count($recipients)) {
                return;
            }

            foreach ($recipients as $language => $recipientsPerLanguage) {
                $title = $this->translator->trans('workflow_change_email_notification_subject', [$subjectType . ' ' . $subject->getFullPath(), $workflow->getName()], 'admin', $language);
                $message = $this->translator->trans(
                    'workflow_change_email_notification_text',
                    [
                        $subjectType . ' ' . $subject->getFullPath(),
                        $subject->getId(),
                        $this->translator->trans($action, [], 'admin', $language),
                        $this->translator->trans($workflow->getName(), [], 'admin', $language),
                    ],
                    'admin',
                    $language
                );

                $noteInfo = $this->getNoteInfo($subject->getId());
                if ($noteInfo) {
                    $message .= "\n\n";
                    $message .= $this->translator->trans('workflow_change_email_notification_note', [], 'admin') . "\n";
                    $message .= $noteInfo;
                }

                foreach ($recipientsPerLanguage as $recipient) {
                    $this->notificationService->sendToUser($recipient->getId(), 0, $title, $message, $subject);
                }
            }
        } catch (Exception) {
            \OpenDxp\Logger::error('Error sending Workflow change notification.');
        }
    }
}
