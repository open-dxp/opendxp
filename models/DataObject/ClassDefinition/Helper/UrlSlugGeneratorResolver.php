<?php

declare(strict_types=1);

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
