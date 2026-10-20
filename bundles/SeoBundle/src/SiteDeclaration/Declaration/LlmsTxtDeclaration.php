<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\SeoBundle\SiteDeclaration\Declaration;

use OpenDxp\Bundle\SeoBundle\SiteDeclaration\SiteDeclarationInterface;

final class LlmsTxtDeclaration implements SiteDeclarationInterface
{
    public function getName(): string
    {
        return 'llms_txt';
    }

    public function getPath(): string
    {
        return '/llms.txt';
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
