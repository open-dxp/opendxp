<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Application\SiteDeclaration;

use OpenDxp\Bundle\SeoBundle\SiteDeclaration\SiteDeclarationInterface;

final class MockSiteDeclaration implements SiteDeclarationInterface
{
    public function __construct(
        private readonly string $name,
        private readonly string $path,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
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
        return [];
    }

    public function validate(string $content): array
    {
        return [];
    }
}
