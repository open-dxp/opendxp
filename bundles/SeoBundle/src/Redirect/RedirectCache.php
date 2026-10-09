<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\SeoBundle\Redirect;

use Doctrine\DBAL\Exception\TableNotFoundException;
use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Cache;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\Service\ResetInterface;
use Throwable;

/**
 * Keeps the regular expressions and the domain redirects in the cache of OpenDXP.
 *
 * Filling and clearing take the same lock, so a process that read the redirects before a change cannot cache them
 * after it.
 *
 * @internal
 */
final class RedirectCache implements ResetInterface
{
    private const string CACHE_KEY = 'system_route_redirect';

    private const array CACHE_TAGS = ['system', 'redirect', 'route'];

    /**
     * A missing SEO bundle is checked again after this many seconds, for example once the migrations ran.
     */
    private const int NOT_INSTALLED_LIFETIME = 60;

    private ?CachedRedirects $cachedRedirects = null;

    public function __construct(
        private readonly LockFactory $lockFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function get(): CachedRedirects
    {
        if ($this->cachedRedirects !== null) {
            return $this->cachedRedirects;
        }

        $cachedRedirects = Cache::load(self::CACHE_KEY);
        if ($cachedRedirects instanceof CachedRedirects) {
            return $this->cachedRedirects = $cachedRedirects;
        }

        $lock = $this->lockFactory->createLock(self::class);
        $lock->acquire(true);

        try {
            $cachedRedirects = Cache::load(self::CACHE_KEY);
            if (!$cachedRedirects instanceof CachedRedirects) {
                $cachedRedirects = $this->load();
            }
        } finally {
            $lock->release();
        }

        return $this->cachedRedirects = $cachedRedirects;
    }

    public function clear(): void
    {
        $lock = $this->lockFactory->createLock(self::class);
        $lock->acquire(true);

        try {
            Cache::clearTag('redirect');
        } finally {
            $lock->release();
        }

        $this->cachedRedirects = null;
    }

    public function reset(): void
    {
        $this->cachedRedirects = null;
    }

    private function load(): CachedRedirects
    {
        try {
            $redirects = new Redirect\Listing();
            $redirects->setCondition('active = 1 AND (regex = 1 OR `type` = ?)', [Redirect::TYPE_DOMAIN]);

            $overridingSources = new Redirect\Listing();
            $overridingSources->setCondition(
                'active = 1 AND (regex IS NULL OR regex = 0) AND `type` <> ? AND priority = 99',
                [Redirect::TYPE_DOMAIN],
            );

            $cachedRedirects = CachedRedirects::fromRedirects(
                $redirects->load(),
                $overridingSources->getTotalCount() > 0,
            );
            Cache::save($cachedRedirects, self::CACHE_KEY, self::CACHE_TAGS, null, 998, true);

            return $cachedRedirects;
        } catch (TableNotFoundException) {
            $cachedRedirects = CachedRedirects::notInstalled();
            Cache::save($cachedRedirects, self::CACHE_KEY, self::CACHE_TAGS, self::NOT_INSTALLED_LIFETIME, 998, true);

            return $cachedRedirects;
        } catch (Throwable $exception) {
            $this->logger->error('Could not read the redirects: {message}', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            // Exact sources are still looked up, and the next request reads the redirects again.
            return CachedRedirects::fromRedirects([], true);
        }
    }
}
