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

namespace OpenDxp\Bundle\SeoBundle\Sitemap\Document;

use OpenDxp\Bundle\SeoBundle\Sitemap\UrlGeneratorInterface;
use OpenDxp\Model\Document;
use OpenDxp\Model\Site;
use RuntimeException;

/**
 * URL generator specific to documents with site support.
 */
class DocumentUrlGenerator implements DocumentUrlGeneratorInterface
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function generateUrl(string $path, array $options = []): string
    {
        return $this->urlGenerator->generateUrl($path, $options);
    }

    public function generateDocumentUrl(Document $document, ?Site $site = null, array $options = []): string
    {
        if ($document instanceof Document\Page && $document->getPrettyUrl()) {
            $prettyUrlSet = true;
            $path = $document->getPrettyUrl();
        } else {
            $prettyUrlSet = false;
            $path = $document->getRealFullPath();
        }
        if ($site instanceof \OpenDxp\Model\Site && !$prettyUrlSet) {
            // strip site prefix from path
            $path = substr($path, strlen($site->getRootDocument()->getRealFullPath()));
        }

        $options = $this->prepareOptions($options, $site);

        return $this->urlGenerator->generateUrl($path, $options);
    }

    protected function prepareOptions(array $options, ?Site $site = null): array
    {
        // set site host as default value if it is not explicitely set via options
        if (!isset($options['host']) && $site instanceof \OpenDxp\Model\Site) {
            $host = $this->hostForSite($site);
            if (!empty($host)) {
                $options['host'] = $host;
            }
        }

        return $options;
    }

    protected function hostForSite(Site $site): string
    {
        $host = $site->getMainDomain();
        if (!empty($host)) {
            return $host;
        }

        foreach ($site->getDomains() as $domain) {
            if (!empty($domain)) {
                $host = $domain;

                break;
            }
        }

        if (empty($host)) {
            throw new RuntimeException(sprintf('Failed to resolve host for site %d', $site->getId()));
        }

        return $host;
    }
}
