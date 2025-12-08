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

namespace OpenDxp\Bundle\SeoBundle\Sitemap\Document\Filter;

use OpenDxp\Bundle\SeoBundle\Sitemap\Document\DocumentGeneratorContext;
use OpenDxp\Bundle\SeoBundle\Sitemap\Element\FilterInterface;
use OpenDxp\Bundle\SeoBundle\Sitemap\Element\GeneratorContextInterface;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Site;

/**
 * Filters document if it is a site root, but doesn't match the current site. This used to exclude
 * sites from the default section.
 */
class SiteRootFilter implements FilterInterface
{
    private ?array $siteRoots = null;

    public function canBeAdded(ElementInterface $element, GeneratorContextInterface $context): bool
    {
        if (!$element instanceof Document) {
            return false;
        }

        $site = null;
        if ($context instanceof DocumentGeneratorContext && $context->hasSite()) {
            $site = $context->getSite();
        }

        return !$this->isExcludedSiteRoot($element, $site);
    }

    public function handlesChildren(ElementInterface $element, GeneratorContextInterface $context): bool
    {
        return $this->canBeAdded($element, $context);
    }

    private function isExcludedSiteRoot(Document $document, ?Site $site = null): bool
    {
        if (null === $this->siteRoots) {
            $sites = (new Site\Listing())->load();

            $this->siteRoots = array_map(fn (Site $site) => $site->getRootId(), $sites);
        }

        if (!in_array($document->getId(), $this->siteRoots, true)) {
            return false;
        }

        // no site, but document is a site root -> exclude
        if (!$site instanceof \OpenDxp\Model\Site) {
            return true;
        }

        // exclude site root if it is not the root of the current site
        return $document->getId() !== $site->getRootId();
    }
}
