<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\SeoBundle\Redirect;

use OpenDxp\Bundle\SeoBundle\Model\Redirect;

/**
 * Holds the regular expressions and the domain redirects, which every request checks.
 *
 * @internal
 */
final readonly class CachedRedirects
{
    /**
     * @param list<Redirect>                $regularExpressions in the order they are tried in
     * @param array<string, list<Redirect>> $domainRedirects    by host, in the order they are tried in
     */
    public function __construct(
        public bool $installed,
        public bool $hasOverridingSources,
        public array $regularExpressions,
        public array $domainRedirects,
    ) {
    }

    public static function notInstalled(): self
    {
        return new self(false, false, [], []);
    }

    /**
     * @param list<Redirect> $redirects the active regular expressions and domain redirects
     */
    public static function fromRedirects(array $redirects, bool $hasOverridingSources): self
    {
        $regularExpressions = [];
        $domainRedirects = [];

        usort($redirects, self::compare(...));

        foreach ($redirects as $redirect) {
            if ($redirect->getType() === Redirect::TYPE_DOMAIN) {
                $domainRedirects[strtolower((string) $redirect->getSource())][] = $redirect;
            } elseif ($redirect->isRegex() && $redirect->hasValidRegex()) {
                $regularExpressions[] = $redirect;
            }
        }

        return new self(true, $hasOverridingSources, $regularExpressions, $domainRedirects);
    }

    /**
     * Puts protected redirects first, then the higher priority, then the older redirect.
     */
    private static function compare(Redirect $a, Redirect $b): int
    {
        return [(int) $b->isProtected(), $b->getPriority(), $a->getId()]
            <=> [(int) $a->isProtected(), $a->getPriority(), $b->getId()];
    }
}
