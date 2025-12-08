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

namespace OpenDxp\Tool;

use OpenDxp;
use OpenDxp\Bundle\CoreBundle\EventListener\Frontend\FullPageCacheListener;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\Document;
use OpenDxp\Model\Site;

final class Frontend
{
    public static function isDocumentInSite(?Site $site, Document $document): bool
    {
        $siteRootDocument = $site?->getRootDocument();

        return !($siteRootDocument && !str_starts_with($document->getRealFullPath() . '/', $siteRootDocument->getRealFullPath() . '/'));
    }

    public static function isDocumentInCurrentSite(Document $document): bool
    {
        if (Site::isSiteRequest()) {
            $site = Site::getCurrentSite();

            return self::isDocumentInSite($site, $document);
        }

        return true;
    }

    public static function getSiteForDocument(Document $document): ?Site
    {
        $siteIdOfDocument = self::getSiteIdForDocument($document);

        if (!$siteIdOfDocument) {
            return null;
        }

        return Site::getById($siteIdOfDocument);
    }

    public static function getSiteIdForDocument(Document $document): ?int
    {
        $siteMapping = self::getSiteMapping();

        foreach ($siteMapping as $sitePath => $id) {
            if (str_starts_with($document->getRealFullPath() . '/', $sitePath . '/')) {
                return $id;
            }
        }

        return null;
    }

    private static function getSiteMapping(): array
    {
        $cacheKey = 'sites_path_mapping';

        if (RuntimeCache::isRegistered($cacheKey)) {
            return RuntimeCache::get($cacheKey);
        }

        $siteMapping = OpenDxp\Cache::load($cacheKey);

        if (!$siteMapping) {
            $siteMapping = [];
            $sites = new Site\Listing();
            $sites->setOrderKey(
                '(SELECT LENGTH(CONCAT(`path`, `key`)) FROM documents WHERE documents.id = sites.rootId) DESC',
                false
            );
            $sites = $sites->load();
            foreach ($sites as $site) {
                $siteMapping[$site->getRootPath()] = $site->getId();
            }
            OpenDxp\Cache::save($siteMapping, $cacheKey, ['system', 'resource'], null, 997);
        }
        RuntimeCache::set($cacheKey, $siteMapping);

        return $siteMapping;
    }

    /**
     * @return false|array{enabled: true, lifetime: int|null}
     */
    public static function isOutputCacheEnabled(): bool|array
    {
        $cacheService = OpenDxp::getContainer()->get(FullPageCacheListener::class);

        if ($cacheService->isEnabled()) {
            return [
                'enabled' => true,
                'lifetime' => $cacheService->getLifetime(),
            ];
        }

        return false;
    }
}
