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

namespace OpenDxp\Model;

use Exception;
use InvalidArgumentException;
use OpenDxp\Cache;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Event\Model\SiteEvent;
use OpenDxp\Event\SiteEvents;
use OpenDxp\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use OpenDxp\Logger;
use OpenDxp\Model\Exception\NotFoundException;
use OpenDxp\Tool\Serialize;

/**
 * @method Site\Dao getDao()
 */
final class Site extends AbstractModel
{
    use RecursionBlockingEventDispatchHelperTrait;

    protected static ?Site $currentSite = null;

    protected ?int $id = null;

    protected array $domains = [];

    /**
     * Contains the ID to the Root-Document
     */
    protected ?int $rootId = null;

    protected ?Document\Page $rootDocument = null;

    protected ?string $rootPath = null;

    protected string $mainDomain = '';

    protected string $errorDocument = '';

    protected array $localizedErrorDocuments = [];

    protected bool $redirectToMainDomain = false;

    protected ?int $creationDate = null;

    protected ?int $modificationDate = null;

    /**
     * @throws Exception
     */
    public static function getById(int $id): ?Site
    {
        $cacheKey = 'site_id_'. $id;

        if (RuntimeCache::isRegistered($cacheKey)) {
            $site = RuntimeCache::get($cacheKey);
        } elseif (!$site = Cache::load($cacheKey)) {
            try {
                $site = new self();
                $site->getDao()->getById($id);
            } catch (NotFoundException) {
                $site = 'failed';
            }

            Cache::save($site, $cacheKey, ['system', 'site'], null, 999);
        }

        if ($site === 'failed' || !$site) {
            $site = null;
        }

        RuntimeCache::set($cacheKey, $site);

        return $site;
    }

    public static function getByRootId(int $id): ?Site
    {
        try {
            $site = new self();
            $site->getDao()->getByRootId($id);

            return $site;
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * @throws Exception
     */
    public static function getByDomain(string $domain): ?Site
    {
        // cached because this is called in the route
        $cacheKey = 'site_domain_'. md5($domain);

        if (RuntimeCache::isRegistered($cacheKey)) {
            $site = RuntimeCache::get($cacheKey);
        } elseif (!$site = Cache::load($cacheKey)) {
            try {
                $site = new self();
                $site->getDao()->getByDomain($domain);
            } catch (NotFoundException) {
                $site = 'failed';
            }

            Cache::save($site, $cacheKey, ['system', 'site'], null, 999);
        }

        if ($site === 'failed' || !$site) {
            $site = null;
        }

        RuntimeCache::set($cacheKey, $site);

        return $site;
    }

    /**
     * @throws Exception
     */
    public static function getBy(mixed $mixed): ?Site
    {
        $site = null;

        if (is_numeric($mixed)) {
            $site = self::getById($mixed);
        } elseif (is_string($mixed)) {
            $site = self::getByDomain($mixed);
        } elseif ($mixed instanceof Site) {
            $site = $mixed;
        }

        return $site;
    }

    public static function create(array $data): Site
    {
        $site = new self();
        self::checkCreateData($data);
        $site->setValues($data);

        return $site;
    }

    /**
     * returns true if the current process/request is inside a site
     */
    public static function isSiteRequest(): bool
    {
        return self::$currentSite instanceof \OpenDxp\Model\Site;
    }

    /**
     * @throws Exception
     */
    public static function getCurrentSite(): Site
    {
        if (self::$currentSite instanceof \OpenDxp\Model\Site) {
            return self::$currentSite;
        }

        throw new Exception('This request/process is not inside a subsite');
    }

    /**
     * Register the current site
     */
    public static function setCurrentSite(Site $site): void
    {
        self::$currentSite = $site;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDomains(): array
    {
        return $this->domains;
    }

    public function getRootId(): ?int
    {
        return $this->rootId;
    }

    public function getRootDocument(): ?Document\Page
    {
        return $this->rootDocument;
    }

    /**
     * @return $this
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return $this
     */
    public function setDomains(array|string $domains): static
    {
        if (is_string($domains)) {
            $domains = Serialize::unserialize($domains);
        }
        if (is_array($domains)) {
            $domains = array_filter($domains);
            array_map(static function ($domain): void {
                //replace all wildcards with a placeholder dummy string
                $wildCardLessDomain = str_replace('*', 'anystring', $domain);
                if (
                    $wildCardLessDomain &&
                    !filter_var(idn_to_ascii($wildCardLessDomain), FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
                ) {
                    throw new InvalidArgumentException(sprintf('Invalid domain name "%s"', $domain));
                }
            }, $domains);
            $this->domains = $domains;
        } else {
            $this->domains = [];
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function setRootId(int $rootId): static
    {
        $this->rootId = $rootId;
        $this->rootDocument = Document\Page::getById($this->rootId);

        return $this;
    }

    /**
     * @return $this
     */
    public function setRootDocument(?Document\Page $rootDocument): static
    {
        $this->rootDocument = $rootDocument;
        $this->rootId = $rootDocument?->getId();

        return $this;
    }

    /**
     * @return $this
     */
    public function setRootPath(?string $path): static
    {
        $this->rootPath = $path;

        return $this;
    }

    public function getRootPath(): ?string
    {
        if (!$this->rootPath && $this->getRootDocument()) {
            return $this->getRootDocument()->getRealFullPath();
        }

        return $this->rootPath;
    }

    public function setErrorDocument(string $errorDocument): void
    {
        $this->errorDocument = $errorDocument;
    }

    public function getErrorDocument(): string
    {
        return $this->errorDocument;
    }

    /**
     * @return $this
     */
    public function setLocalizedErrorDocuments(array|string $localizedErrorDocuments): static
    {
        if (is_string($localizedErrorDocuments)) {
            $localizedErrorDocuments = Serialize::unserialize($localizedErrorDocuments);
        }
        $this->localizedErrorDocuments = $localizedErrorDocuments;

        return $this;
    }

    public function getLocalizedErrorDocuments(): array
    {
        return $this->localizedErrorDocuments;
    }

    public function setMainDomain(string $mainDomain): void
    {
        if ($mainDomain && !filter_var(idn_to_ascii($mainDomain), FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new InvalidArgumentException(sprintf('Invalid main domain name "%s"', $mainDomain));
        }
        $this->mainDomain = $mainDomain;
    }

    public function getMainDomain(): string
    {
        return $this->mainDomain;
    }

    public function setRedirectToMainDomain(bool $redirectToMainDomain): void
    {
        $this->redirectToMainDomain = $redirectToMainDomain;
    }

    public function getRedirectToMainDomain(): bool
    {
        return $this->redirectToMainDomain;
    }

    /**
     * @internal
     */
    public function clearDependentCache(): void
    {
        // this is mostly called in Site\Dao not here
        try {
            Cache::clearTag('site');
        } catch (Exception $e) {
            Logger::crit((string) $e);
        }
    }

    /**
     * @return $this
     */
    public function setModificationDate(int $modificationDate): static
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    /**
     * @return $this
     */
    public function setCreationDate(int $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function save(): void
    {
        $preSaveEvent = new SiteEvent($this);
        $this->dispatchEvent($preSaveEvent, SiteEvents::PRE_SAVE);

        $this->getDao()->save();

        $postSaveEvent = new SiteEvent($this);
        $this->dispatchEvent($postSaveEvent, SiteEvents::POST_SAVE);
    }

    public function delete(): void
    {
        $preDeleteEvent = new SiteEvent($this);
        $this->dispatchEvent($preDeleteEvent, SiteEvents::PRE_DELETE);

        $this->getDao()->delete();

        $postDeleteEvent = new SiteEvent($this);
        $this->dispatchEvent($postDeleteEvent, SiteEvents::POST_DELETE);
    }
}
