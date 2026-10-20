<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Application\SiteDeclaration;

use OpenDxp\Bundle\SeoBundle\SiteDeclaration\SiteDeclarationInterface;

final class AppleAppSiteAssociationDeclaration implements SiteDeclarationInterface
{
    public function getName(): string
    {
        return 'apple_app_site_association';
    }

    public function getPath(): string
    {
        return '/.well-known/apple-app-site-association';
    }

    public function getContentType(): string
    {
        return 'application/json';
    }

    public function getDefaultContent(): ?string
    {
        return null;
    }

    public function getPlaceholders(): array
    {
        return [];
    }

    public function validate(string $content): array
    {
        return [];
    }
}
