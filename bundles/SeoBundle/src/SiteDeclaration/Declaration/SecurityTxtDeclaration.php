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

namespace OpenDxp\Bundle\SeoBundle\SiteDeclaration\Declaration;

use Carbon\Carbon;
use OpenDxp\Bundle\SeoBundle\SiteDeclaration\SiteDeclarationInterface;
use OpenDxp\Bundle\SeoBundle\SiteDeclaration\SiteDeclarationPlaceholder;

final class SecurityTxtDeclaration implements SiteDeclarationInterface
{
    public function getName(): string
    {
        return 'security_txt';
    }

    public function getPath(): string
    {
        return '/.well-known/security.txt';
    }

    public function getContentType(): string
    {
        return 'text/plain; charset=utf-8';
    }

    public function getDefaultContent(): ?string
    {
        return null;
    }

    public function getPlaceholders(): array
    {
        // RFC 9116 recommends an expiry date less than a year ahead.
        $expires = Carbon::now('UTC')->startOfDay()->addDays(364);

        return [
            new SiteDeclarationPlaceholder(
                name: 'expires',
                tooltipTranslationKey: 'site_declaration_placeholder_expires',
                value: $expires->format('Y-m-d\TH:i:s\Z'),
            ),
        ];
    }

    public function validate(string $content): array
    {
        $errors = [];

        if (preg_match('/^Contact:/im', $content) !== 1) {
            $errors[] = 'site_declaration_security_txt_contact_missing';
        }

        $expiresCount = preg_match_all('/^Expires:/im', $content);

        if ($expiresCount === 0) {
            $errors[] = 'site_declaration_security_txt_expires_missing';
        }

        if ($expiresCount > 1) {
            $errors[] = 'site_declaration_security_txt_expires_repeated';
        }

        return $errors;
    }
}
