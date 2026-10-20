<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\SeoBundle\SiteDeclaration;

final readonly class SiteDeclarationPlaceholder
{
    public function __construct(
        public string $name,
        public string $tooltipTranslationKey,
        public string $value,
    ) {
    }
}
