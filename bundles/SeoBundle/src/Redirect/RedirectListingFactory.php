<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\SeoBundle\Redirect;

use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Model\Site;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
final class RedirectListingFactory
{
    private const int UNUSED_AFTER = 90 * 86400;

    public function __construct(private readonly RedirectHandler $redirectHandler)
    {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function create(array $parameters, bool $mayManageProtected): Redirect\Listing
    {
        $list = new Redirect\Listing();

        $sortingSettings = QueryParams::extractSortingSettings($parameters);
        if (in_array($sortingSettings['orderKey'], ['hits', 'lastHit'], true)) {
            $hitColumn = sprintf(
                '(SELECT `%s` FROM redirect_hits WHERE redirectId = redirects.id)',
                $sortingSettings['orderKey'],
            );
            $list->setOrderKey($hitColumn, false);
            $list->setOrder($sortingSettings['order']);
        } elseif ($sortingSettings['orderKey']) {
            $list->setOrderKey($sortingSettings['orderKey']);
            $list->setOrder($sortingSettings['order']);
        }

        $conditions = $mayManageProtected ? [] : ['protected = 0'];
        $variables = [];

        $now = time();
        $shown = match ((string) ($parameters['show'] ?? '')) {
            'active' => 'active = 1',
            'inactive' => '(active = 0 OR active IS NULL)',
            'expired' => sprintf('(expiry IS NOT NULL AND expiry <= %d)', $now),
            'scheduled' => sprintf('validFrom > %d', $now),
            'protected' => 'protected = 1',
            'unused' => sprintf(
                'creationDate < %1$d AND id NOT IN (SELECT redirectId FROM redirect_hits WHERE lastHit >= %1$d)',
                $now - self::UNUSED_AFTER,
            ),
            default => null,
        };
        if ($shown !== null) {
            $conditions[] = $shown;
        }

        if ($filterValue = (string) ($parameters['filter'] ?? '')) {
            if (is_numeric($filterValue)) {
                $conditions[] = 'id = ?';
                $variables[] = $filterValue;
            } elseif (preg_match('@^https?://@', $filterValue)) {
                $dummyRequest = Request::create($filterValue);
                $site = Site::getByDomain($dummyRequest->getHost());
                $dummyResponse = $this->redirectHandler->checkForDomainRedirect($dummyRequest)
                    ?? $this->redirectHandler->checkForRedirect($dummyRequest, true, $site)
                    ?? $this->redirectHandler->checkForRedirect($dummyRequest, false, $site);

                $conditions[] = 'id = ?';
                $variables[] = (int) $dummyResponse?->headers->get(RedirectHandler::RESPONSE_HEADER_NAME_ID);
            } else {
                $conditions[] = '(`source` LIKE ? OR `target` LIKE ?)';
                $variables[] = '%' . $filterValue . '%';
                $variables[] = '%' . $filterValue . '%';
            }
        }

        if ($conditions !== []) {
            $list->setCondition(implode(' AND ', $conditions), $variables);
        }

        return $list;
    }
}
