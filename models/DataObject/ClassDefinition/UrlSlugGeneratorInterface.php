<?php

declare(strict_types=1);

namespace OpenDxp\Model\DataObject\ClassDefinition;

interface UrlSlugGeneratorInterface
{
    /**
     * Returns the path in front of every slug of the context, for example /en/products.
     * Null means that the slug has no prefix.
     */
    public function getPrefix(UrlSlugContext $context): ?string;

    /**
     * Turns the text an editor typed behind the prefix into the part of the slug behind the prefix.
     */
    public function formatSlug(string $text, UrlSlugContext $context): string;

    /**
     * Returns the text for an empty slug, for example the title of the object.
     * Core formats it with formatSlug(). Null leaves the slug empty.
     */
    public function getDefaultSlug(UrlSlugContext $context): ?string;
}
