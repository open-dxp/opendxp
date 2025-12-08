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

namespace OpenDxp\Bundle\SeoBundle\EventListener;

use OpenDxp;
use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Bundle\SeoBundle\OpenDxpSeoBundle;
use OpenDxp\Db;
use OpenDxp\Event\Model\DataObject\ClassDefinition\UrlSlugEvent;
use OpenDxp\Event\UrlSlugEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UrlSlugUpdateListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            UrlSlugEvents::POST_SAVE => 'onURLSlugUpdate',
        ];
    }

    public function onURLSlugUpdate(UrlSlugEvent $event): void
    {
        if (!OpenDxpSeoBundle::isInstalled()) {
            return;
        }

        $opendxp_seo_redirects = OpenDxp::getContainer()->getParameter('opendxp_seo.redirects');
        $data = $event->getData();
        // check for previous slugs and create redirects
        if (!$opendxp_seo_redirects['auto_create_redirects']) {
            return;
        }

        $db = Db::get();
        foreach ($data as $slug) {
            if ($previousSlug = $slug->getPreviousSlug()) {
                if ($previousSlug === $slug->getSlug()) {
                    continue;
                }
                if (!$slug->getSlug()) {
                    continue;
                }
                $checkSql = 'SELECT id FROM redirects WHERE source = :sourcePath AND `type` = :typeAuto';
                if ($slug->getSiteId()) {
                    $checkSql .= ' AND sourceSite = ' . $db->quote($slug->getSiteId());
                } else {
                    $checkSql .= ' AND sourceSite IS NULL';
                }
                $existingCheck = $db->fetchOne($checkSql, ['sourcePath' => $previousSlug, 'typeAuto' => Redirect::TYPE_AUTO_CREATE]);
                if (!$existingCheck) {
                    $redirect = new Redirect();
                    $redirect->setType(Redirect::TYPE_AUTO_CREATE);
                    $redirect->setRegex(false);
                    $redirect->setTarget($slug->getSlug());
                    $redirect->setSource($previousSlug);
                    $redirect->setStatusCode(301);
                    $redirect->setExpiry(time() + 86400 * 365); // this entry is removed automatically after 1 year

                    if ($slug->getSiteId()) {
                        $redirect->setSourceSite($slug->getSiteId());
                        $redirect->setTargetSite($slug->getSiteId());
                    }

                    $redirect->save();
                }
                $slug->setPreviousSlug(null);
            }
        }
    }
}
