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

namespace OpenDxp\Tests\Application\Service;

use OpenDxp\Model\DataObject\ClassDefinition\UrlSlugContext;
use OpenDxp\Model\DataObject\ClassDefinition\UrlSlugGeneratorInterface;
use RuntimeException;

final class FailingSlugGenerator implements UrlSlugGeneratorInterface
{
    public function getPrefix(UrlSlugContext $context): string
    {
        throw new RuntimeException('The generator has no prefix.');
    }

    public function formatSlug(string $text, UrlSlugContext $context): string
    {
        return $text;
    }

    public function getDefaultSlug(UrlSlugContext $context): ?string
    {
        return null;
    }
}
