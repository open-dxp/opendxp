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

namespace OpenDxp\Model\DataObject\ClassDefinition;

interface UrlSlugGeneratorInterface
{
    /**
     * Returns the path in front of every slug of the context, for example /en/products.
     * Null means that the slug has no prefix.
     */
    public function getPrefix(UrlSlugContext $context): ?string;

    /**
     * Turns the text an editor typed behind the prefix into the part of the slug behind the prefix.
     */
    public function formatSlug(string $text, UrlSlugContext $context): string;

    /**
     * Returns the text for an empty slug, for example the title of the object.
     * Core formats it with formatSlug(). Null leaves the slug empty.
     */
    public function getDefaultSlug(UrlSlugContext $context): ?string;
}
