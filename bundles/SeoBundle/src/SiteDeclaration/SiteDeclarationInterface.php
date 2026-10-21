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

namespace OpenDxp\Bundle\SeoBundle\SiteDeclaration;

interface SiteDeclarationInterface
{
    /**
     * Returns a unique name in snake case with at most 33 characters, like "security_txt".
     */
    public function getName(): string;

    public function getPath(): string;

    public function getContentType(): string;

    /**
     * Returns the content for a site without content. Null answers with 404.
     */
    public function getDefaultContent(): ?string;

    /**
     * @return list<SiteDeclarationPlaceholder>
     */
    public function getPlaceholders(): array;

    /**
     * Returns the translation keys of the errors in the content.
     *
     * @return list<string>
     */
    public function validate(string $content): array;
}
