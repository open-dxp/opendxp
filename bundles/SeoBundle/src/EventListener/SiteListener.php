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

namespace OpenDxp\Bundle\SeoBundle\EventListener;

use OpenDxp\Bundle\SeoBundle\SiteDeclaration\SiteDeclarationRepository;
use OpenDxp\Event\Model\SiteEvent;
use OpenDxp\Event\SiteEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SiteListener implements EventSubscriberInterface
{
    public function __construct(private readonly SiteDeclarationRepository $siteDeclarationRepository)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SiteEvents::POST_DELETE => 'onSiteDelete',
        ];
    }

    public function onSiteDelete(SiteEvent $event): void
    {
        $this->siteDeclarationRepository->deleteContents($event->getSite()->getId());
    }
}
