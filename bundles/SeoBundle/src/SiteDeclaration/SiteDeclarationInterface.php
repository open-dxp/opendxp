<?php

declare(strict_types=1);

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
