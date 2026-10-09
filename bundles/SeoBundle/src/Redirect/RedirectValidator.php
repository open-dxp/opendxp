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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

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
    public function validate(Redirect $redirect, bool $mayManageProtected): RedirectValidationResult
    {
        $result = new RedirectValidationResult();
        $source = (string) $redirect->getSource();

        if ($source === '') {
            return $result->withError('source', 'redirect_source_missing');
        }

        if ($redirect->isRegex() && !$redirect->hasValidRegex()) {
            $result = $result->withError('source', 'redirect_regex_invalid');
        }

        $validFrom = $redirect->getValidFrom();
        $expiry = $redirect->getExpiry();
        if ($validFrom !== null && $expiry !== null && $expiry <= $validFrom) {
            $result = $result->withError('expiry', 'redirect_expires_before_start');
        }

        if ($redirect->getType() === Redirect::TYPE_DOMAIN) {
            $result = $this->validateDomain($redirect, $result);
        } elseif (!$redirect->isRegex() && $this->pointsToItself($redirect)) {
            $result = $result->withError('target', 'redirect_loop');
        }

        if ($redirect->isRegex()) {
            return $result;
        }

        foreach ($this->sameSource($redirect) as $other) {
            if ($other['protected'] && !$mayManageProtected) {
                return $result->withError('source', 'redirect_source_protected');
            }

            $result = $result->withWarning('redirect_source_duplicate', ['%id%' => $other['id']]);
        }

        if (($chained = $this->redirectFromTarget($redirect)) !== null) {
            $result = $result->withWarning('redirect_chain', ['%id%' => $chained]);
        }

        return $result;
    }

    private function validateDomain(Redirect $redirect, RedirectValidationResult $result): RedirectValidationResult
    {
        if (preg_match('/^[a-z0-9.-]+$/i', (string) $redirect->getSource()) !== 1) {
            $result = $result->withError('source', 'redirect_domain_source_invalid');
        }

        // A relative target stays on the same host, so the domain would redirect to itself.
        if (!$redirect->getTargetSite() && preg_match('@^https?://@i', (string) $redirect->getTarget()) !== 1) {
            $result = $result->withError('target', 'redirect_domain_target_relative');
        }

        return $result;
    }

    private function pointsToItself(Redirect $redirect): bool
    {
        if (!in_array($redirect->getType(), self::PATH_TYPES, true)) {
            return false;
        }

        if ($redirect->getTargetSite() && $redirect->getTargetSite() !== $redirect->getSourceSite()) {
            return false;
        }

        // The database compares like a request does, regardless of case and accents.
        return (bool) $this->db->fetchOne(
            'SELECT CAST(:target AS CHAR) = CAST(:source AS CHAR)',
            [
                'target' => $redirect->getTargetPath(),
                'source' => (string) $redirect->getSource(),
            ],
        );
    }

    /**
     * Returns the other exact redirects that answer the same requests. The database compares the sources like a request
     * does, regardless of case and accents.
     *
     * A source is compared with:
     * - the other domain redirects of its host, on every site
     * - the exact sources of every other type, on the same site
     *
     * @return list<array{id: int, protected: bool}>
     */
    private function sameSource(Redirect $redirect): array
    {
        $isDomain = $redirect->getType() === Redirect::TYPE_DOMAIN;

        $rows = $this->db->fetchAllAssociative(
            'SELECT id, protected FROM redirects
                WHERE source = :source AND (type = :domain) = :isDomain AND (regex IS NULL OR regex = 0)
                    AND (:isDomain = 1 OR sourceSite <=> :site) AND id <> :id
                ORDER BY protected DESC, id',
            [
                'source' => $redirect->getSource(),
                'domain' => Redirect::TYPE_DOMAIN,
                'isDomain' => (int) $isDomain,
                'site' => $redirect->getSourceSite(),
                'id' => (int) $redirect->getId(),
            ],
        );

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'protected' => (bool) $row['protected'],
            ],
            $rows,
        );
    }

    private function redirectFromTarget(Redirect $redirect): ?int
    {
        if ($redirect->getType() === Redirect::TYPE_DOMAIN) {
            return null;
        }

        $id = $this->db->fetchOne(
            'SELECT id FROM redirects
                WHERE source = :target AND type IN (:types) AND (regex IS NULL OR regex = 0) AND active = 1
                    AND sourceSite <=> :site AND id <> :id',
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
