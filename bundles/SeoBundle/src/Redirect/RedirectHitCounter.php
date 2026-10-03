<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\SeoBundle\Redirect;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;
use Throwable;

/**
 * Counts how often each redirect answers a request.
 *
 * The count is written once the response has been sent, so a visitor never waits for it. Counting can be switched off
 * with opendxp_seo.redirects.count_hits.
 *
 * @internal
 */
final class RedirectHitCounter implements EventSubscriberInterface, ResetInterface
{
    /**
     * @var list<int>
     */
    private array $hits = [];

    /**
     * @param array{count_hits: bool} $config
     */
    public function __construct(
        private readonly Connection $db,
        private readonly LoggerInterface $logger,
        #[Autowire(param: 'opendxp_seo.redirects')]
        private readonly array $config,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'flush',
        ];
    }

    public function count(Response $response): void
    {
        $redirectId = $response->headers->get(RedirectHandler::RESPONSE_HEADER_NAME_ID);

        if ($this->config['count_hits'] && is_numeric($redirectId)) {
            $this->hits[] = (int) $redirectId;
        }
    }

    public function flush(): void
    {
        $hits = $this->hits;
        $this->hits = [];

        foreach ($hits as $redirectId) {
            try {
                $this->db->executeStatement(
                    'INSERT INTO redirect_hits (redirectId, hits, lastHit) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE hits = hits + 1, lastHit = VALUES(lastHit)',
                    [$redirectId, time()]
                );
            } catch (Throwable $exception) {
                $this->logger->warning('Could not count the hit of redirect {redirect}: {message}', ['redirect' => $redirectId, 'message' => $exception->getMessage()]);
            }
        }
    }

    public function reset(): void
    {
        $this->hits = [];
    }
}
