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

namespace OpenDxp\Tests\TestCase;

use OpenDxp\Bundle\SeoBundle\SiteDeclaration\SiteDeclarationRegistry;
use OpenDxp\Bundle\SeoBundle\SiteDeclaration\SiteDeclarationRepository;
use OpenDxp\TestFoundation\Container;
use OpenDxp\TestFoundation\EnvironmentTestCase;

abstract class SiteDeclarationTestCase extends EnvironmentTestCase
{
    protected static function environment(): string
    {
        return 'site_declarations';
    }

    protected function saveContent(string $name, int $siteId, string $content): void
    {
        $declaration = Container::get(SiteDeclarationRegistry::class)->getDeclaration($name);

        Container::get(SiteDeclarationRepository::class)->saveContent($declaration, $siteId, $content);
    }
}
