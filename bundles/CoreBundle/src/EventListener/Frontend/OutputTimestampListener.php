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

namespace OpenDxp\Bundle\CoreBundle\EventListener\Frontend;

use OpenDxp;
use OpenDxp\Http\Request\Resolver\OutputTimestampResolver;
use OpenDxp\Tool\Authentication;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class OutputTimestampListener implements EventSubscriberInterface
{
    const TIMESTAMP_OVERRIDE_PARAM_NAME = 'opendxp_override_output_timestamp';

    public function __construct(protected OutputTimestampResolver $outputTimestampResolver)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (($overrideTimestamp = (int)$event->getRequest()->query->get(self::TIMESTAMP_OVERRIDE_PARAM_NAME)) && (OpenDxp::inDebugMode() || Authentication::authenticateSession($event->getRequest()))) {
            $this->outputTimestampResolver->setOutputTimestamp($overrideTimestamp);
        }
    }
}
