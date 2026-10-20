<?php

declare(strict_types=1);

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
