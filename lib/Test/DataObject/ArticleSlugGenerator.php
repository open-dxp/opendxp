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

namespace OpenDxp\Test\DataObject;

use OpenDxp\Model\DataObject\ClassDefinition\UrlSlugContext;
use OpenDxp\Model\DataObject\ClassDefinition\UrlSlugGeneratorInterface;

final class ArticleSlugGenerator implements UrlSlugGeneratorInterface
{
    public function getPrefix(UrlSlugContext $context): string
    {
        return implode('/', array_filter([
            $context->language,
            $context->site ? 'site-' . $context->site->getId() : null,
            'articles',
        ]));
    }

    public function formatSlug(string $text, UrlSlugContext $context): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($text)), '-');
    }

    public function getDefaultSlug(UrlSlugContext $context): ?string
    {
        return $context->language === null
            ? $context->object->get('title')
            : $context->object->get('headline', $context->language);
    }
}
