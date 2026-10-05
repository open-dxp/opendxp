<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\SeoBundle\Redirect;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use OpenDxp\Bundle\SeoBundle\Model\Redirect;

/**
 * Checks a redirect before an editor saves it.
 *
 * An error stops the save. A warning is shown, and the redirect is saved anyway.
 *
 * @internal
 */
final readonly class RedirectValidator
{
    private const array PATH_TYPES = [Redirect::TYPE_PATH, Redirect::TYPE_AUTO_CREATE];

    public function __construct(private Connection $db)
    {
    }

    /**
     * @param bool $mayManageProtected whether the editor holds the permission redirects_protected
     */
    public function validate(Redirect $redirect, bool $mayManageProtected): RedirectValidation
    {
        $validation = new RedirectValidation();
        $source = (string) $redirect->getSource();

        if ($source === '') {
            return $validation->withError('source', 'redirect_source_missing');
        }

        if ($redirect->isRegex() && !$this->compiles($source)) {
            $validation = $validation->withError('source', 'redirect_regex_invalid');
        }

        if ($redirect->getValidFrom() !== null && $redirect->getExpiry() !== null && $redirect->getExpiry() <= $redirect->getValidFrom()) {
            $validation = $validation->withError('expiry', 'redirect_expires_before_start');
        }

        if ($redirect->getType() === Redirect::TYPE_DOMAIN) {
            $validation = $this->validateDomain($redirect, $validation);
        } elseif (!$redirect->isRegex() && $this->pointsToItself($redirect)) {
            $validation = $validation->withError('target', 'redirect_loop');
        }

        if ($redirect->isRegex()) {
            return $validation;
        }

        foreach ($this->sameSource($redirect) as $other) {
            if ($other['protected'] && !$mayManageProtected) {
                return $validation->withError('source', 'redirect_source_protected');
            }

            $validation = $validation->withWarning('redirect_source_duplicate', ['%id%' => $other['id']]);
        }

        if (($chained = $this->redirectFromTarget($redirect)) !== null) {
            $validation = $validation->withWarning('redirect_chain', ['%id%' => $chained]);
        }

        return $validation;
    }

    private function compiles(string $pattern): bool
    {
        // An invalid pattern raises a warning, which is the answer here and not an error of the application.
        set_error_handler(static fn (): bool => true);

        try {
            return preg_match($pattern, '') !== false;
        } finally {
            restore_error_handler();
        }
    }

    private function validateDomain(Redirect $redirect, RedirectValidation $validation): RedirectValidation
    {
        if (preg_match('/^[a-z0-9.-]+(:\d+)?$/i', (string) $redirect->getSource()) !== 1) {
            $validation = $validation->withError('source', 'redirect_domain_source_invalid');
        }

        // A relative target stays on the host of the request, which is the very domain that redirects.
        if (!$redirect->getTargetSite() && preg_match('@^https?://@i', (string) $redirect->getTarget()) !== 1) {
            $validation = $validation->withError('target', 'redirect_domain_target_relative');
        }

        return $validation;
    }

    private function pointsToItself(Redirect $redirect): bool
    {
        if (!in_array($redirect->getType(), self::PATH_TYPES, true)) {
            return false;
        }

        if ($redirect->getTargetSite() && $redirect->getTargetSite() !== $redirect->getSourceSite()) {
            return false;
        }

        return RedirectTable::normalize($redirect->getTargetPath()) === RedirectTable::normalize((string) $redirect->getSource());
    }

    /**
     * The database compares sources regardless of case and accents, just like a request is matched.
     *
     * @return list<array{id: int, protected: bool}>
     */
    private function sameSource(Redirect $redirect): array
    {
        $types = in_array($redirect->getType(), self::PATH_TYPES, true) ? self::PATH_TYPES : [$redirect->getType()];

        $rows = $this->db->fetchAllAssociative(
            'SELECT id, protected FROM redirects
                WHERE source = :source AND type IN (:types) AND (regex IS NULL OR regex = 0) AND sourceSite <=> :site AND id <> :id
                ORDER BY protected DESC, id',
            ['source' => $redirect->getSource(), 'types' => $types, 'site' => $redirect->getSourceSite(), 'id' => (int) $redirect->getId()],
            ['types' => ArrayParameterType::STRING]
        );

        return array_map(static fn (array $row): array => ['id' => (int) $row['id'], 'protected' => (bool) $row['protected']], $rows);
    }

    private function redirectFromTarget(Redirect $redirect): ?int
    {
        if ($redirect->getType() === Redirect::TYPE_DOMAIN) {
            return null;
        }

        $id = $this->db->fetchOne(
            'SELECT id FROM redirects WHERE source = :target AND type IN (:types) AND (regex IS NULL OR regex = 0) AND active = 1 AND sourceSite <=> :site AND id <> :id',
            [
                'target' => $redirect->getTargetPath(),
                'types' => self::PATH_TYPES,
                'site' => $redirect->getTargetSite() ?: $redirect->getSourceSite(),
                'id' => (int) $redirect->getId(),
            ],
            ['types' => ArrayParameterType::STRING]
        );

        return $id === false ? null : (int) $id;
    }
}
