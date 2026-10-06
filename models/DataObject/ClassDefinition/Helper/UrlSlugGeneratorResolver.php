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

namespace OpenDxp\Model\DataObject\ClassDefinition\Helper;

use OpenDxp\Model\DataObject\ClassDefinition\UrlSlugGeneratorInterface;

class UrlSlugGeneratorResolver extends ClassResolver
{
    public static function resolveGenerator(string $generatorClass): ?UrlSlugGeneratorInterface
    {
        /** @var UrlSlugGeneratorInterface|null $generator */
        $generator = self::resolve(
            $generatorClass,
            static fn ($generator) => $generator instanceof UrlSlugGeneratorInterface,
        );

        return $generator;
    }
}
