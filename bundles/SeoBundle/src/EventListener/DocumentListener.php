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
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Event\Model\DocumentEvent;
use OpenDxp\Model\Document;
use OpenDxp\Model\Document\Hardlink;
use OpenDxp\Model\Document\Page;
use OpenDxp\Model\Site;
use OpenDxp\Tool\Frontend;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DocumentListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DocumentEvents::POST_DELETE => 'onDocumentDelete',
            DocumentEvents::PAGE_POST_SAVE_ACTION => 'onPagePostSaveAction',
            DocumentEvents::POST_MOVE_ACTION => 'onPostMoveAction',
        ];
    }

    public function onDocumentDelete(DocumentEvent $event): void
    {
        if (!OpenDxpSeoBundle::isInstalled()) {
            return;
        }

        $document = $event->getDocument();
        if ($document instanceof Page || $document instanceof Hardlink) {
            // check for redirects pointing to this document, and delete them too
            $redirects = new Redirect\Listing();
            $redirects->setCondition('target = ?', $document->getId());
            $redirects->load();

            foreach ($redirects->getRedirects() as $redirect) {
                $redirect->delete();
            }
        }
    }

    public function onPagePostSaveAction(DocumentEvent $event): void
    {
        if (!OpenDxpSeoBundle::isInstalled()) {
            return;
        }

        $page = $event->getDocument();
        $opendxp_seo_redirects = OpenDxp::getContainer()->getParameter('opendxp_seo.redirects');
        if ($page instanceof Page && $opendxp_seo_redirects['auto_create_redirects']) {
            $oldPage = $event->getArgument('oldPage');
            $task = $event->getArgument('task');
            if (($task === 'publish' || $task === 'unpublish') && ($page->getPrettyUrl() !== $oldPage->getPrettyUrl() && empty($oldPage->getPrettyUrl()) === false && empty($page->getPrettyUrl()) === false)) {
                $redirect = new Redirect();
                $redirect->setSource($oldPage->getPrettyUrl());
                $redirect->setTarget($page->getPrettyUrl());
                $redirect->setStatusCode(301);
                $redirect->setType(Redirect::TYPE_AUTO_CREATE);
                $redirect->save();
            }
        }
    }

    public function onPostMoveAction(DocumentEvent $event): void
    {
        if (!OpenDxpSeoBundle::isInstalled()) {
            return;
        }

        $document = $event->getDocument();
        $oldDocument = $event->getArgument('oldDocument');
        $oldPath = $event->getArgument('oldPath');
        $this->createRedirectForFormerPath($document, $oldPath, $oldDocument);
    }

    private function createRedirectForFormerPath(Document $document, string $oldPath, Document $oldDocument): void
    {
        $opendxp_seo_redirects = OpenDxp::getContainer()->getParameter('opendxp_seo.redirects');
        if (($document instanceof Document\Page || $document instanceof Document\Hardlink) && (OpenDxp\Tool\Admin::getCurrentUser()->isAllowed('redirects') && $opendxp_seo_redirects['auto_create_redirects'])) {
            $sourceSite = Frontend::getSiteForDocument($oldDocument);
            if ($sourceSite) {
                $oldPath = preg_replace('@^' . preg_quote($sourceSite->getRootPath(), '@') . '@', '', $oldPath);
            }
            $targetSite = Frontend::getSiteForDocument($document);
            $this->doCreateRedirectForFormerPath($oldPath, $document->getId(), $sourceSite, $targetSite);
            if ($document->hasChildren()) {
                $list = new Document\Listing();
                $list->setCondition('`path` LIKE :path', [
                    'path' => $list->escapeLike($document->getRealFullPath()) . '/%',
                ]);

                $childrenList = $list->loadIdPathList();

                $count = 0;

                foreach ($childrenList as $child) {
                    $source = preg_replace('@^' . preg_quote($document->getRealFullPath(), '@') . '@', $oldDocument->getRealFullPath(), $child['path']);
                    if ($sourceSite) {
                        $source = preg_replace('@^' . preg_quote($sourceSite->getRootPath(), '@') . '@', '', $source);
                    }

                    $target = $child['id'];

                    $this->doCreateRedirectForFormerPath($source, $target, $sourceSite, $targetSite);

                    $count++;
                    if ($count % 10 === 0) {
                        OpenDxp::collectGarbage();
                    }
                }
            }
        }
    }

    private function doCreateRedirectForFormerPath(string $source, int $targetId, ?Site $sourceSite, ?Site $targetSite): void
    {
        $redirect = new Redirect();
        $redirect->setType(Redirect::TYPE_AUTO_CREATE);
        $redirect->setRegex(false);
        $redirect->setTarget((string) $targetId);
        $redirect->setSource($source);
        $redirect->setStatusCode(301);
        $redirect->setExpiry(time() + 86400 * 365); // this entry is removed automatically after 1 year

        if ($sourceSite) {
            $redirect->setSourceSite($sourceSite->getId());
        }

        if ($targetSite) {
            $redirect->setTargetSite($targetSite->getId());
        }

        $redirect->save();
    }
}
