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

use OpenDxp\Bundle\CoreBundle\EventListener\Traits\OpenDxpContextAwareTrait;
use OpenDxp\Bundle\StaticRoutesBundle\Model\Staticroute;
use OpenDxp\Http\Request\Resolver\DocumentResolver;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use OpenDxp\Model\Document;
use OpenDxp\Model\Document\Hardlink\Wrapper\WrapperInterface;
use OpenDxp\Model\Site;
use OpenDxp\Tool\Frontend;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets canonical headers for hardlink documents
 *
 * @internal
 */
class HardlinkCanonicalListener implements EventSubscriberInterface
{
    use OpenDxpContextAwareTrait;

    public function __construct(protected DocumentResolver $documentResolver)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->matchesOpenDxpContext($request, OpenDxpContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if (class_exists(Staticroute::class) && Staticroute::getCurrentRoute() instanceof \OpenDxp\Bundle\StaticRoutesBundle\Model\Staticroute) {
            return;
        }

        $document = $this->documentResolver->getDocument($request);
        if (!$document) {
            return;
        }

        if ($document instanceof WrapperInterface) {
            $this->handleHardlink($request, $event->getResponse(), $document);
        }
    }

    protected function handleHardlink(Request $request, Response $response, Document $document): void
    {
        $canonical = null;

        // get the canonical (source) document
        $hardlinkCanonicalSourceDocument = Document::getById($document->getId());

        if (Frontend::isDocumentInCurrentSite($hardlinkCanonicalSourceDocument)) {
            $canonical = $request->getSchemeAndHttpHost() . $hardlinkCanonicalSourceDocument->getFullPath();
        } elseif (Site::isSiteRequest()) {
            $sourceSite = Frontend::getSiteForDocument($hardlinkCanonicalSourceDocument);
            if ($sourceSite && $sourceSite->getMainDomain()) {
                $sourceSiteRelPath = preg_replace('@^' . preg_quote($sourceSite->getRootPath(), '@') . '@', '', $hardlinkCanonicalSourceDocument->getRealFullPath());
                $canonical = $request->getScheme() . '://' . $sourceSite->getMainDomain() . $sourceSiteRelPath;
            }
        }

        if ($canonical) {
            $response->headers->set('Link', '<' . $canonical . '>; rel="canonical"', false);
        }
    }
}
