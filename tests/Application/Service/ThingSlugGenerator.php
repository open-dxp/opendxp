<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Application\Service;

use OpenDxp\Model\DataObject\ClassDefinition\UrlSlugContext;
use OpenDxp\Model\DataObject\ClassDefinition\UrlSlugGeneratorInterface;

final class ThingSlugGenerator implements UrlSlugGeneratorInterface
{
    public function getPrefix(UrlSlugContext $context): string
    {
        return implode('/', array_filter([
            $context->language,
            $context->site ? 'site-' . $context->site->getId() : null,
            'things',
        ]));
    }

    public function formatSlug(string $text, UrlSlugContext $context): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($text)), '-');
    }

    public function getDefaultSlug(UrlSlugContext $context): ?string
    {
        return $context->language === null
            ? $context->object->get('name')
            : $context->object->get('lname', $context->language);
    }
}
