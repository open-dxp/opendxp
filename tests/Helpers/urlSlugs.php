<?php

declare(strict_types=1);

use OpenDxp\Model\DataObject\ClassDefinition\Data\Localizedfields;
use OpenDxp\Model\DataObject\ClassDefinition\Data\UrlSlug as UrlSlugField;
use OpenDxp\Model\DataObject\Data\UrlSlug;

/**
 * @param UrlSlug[]|null $slugs
 *
 * @return array<int, string>
 */
function slugsBySite(?array $slugs): array
{
    $bySite = [];

    foreach ($slugs ?? [] as $slug) {
        $bySite[$slug->getSiteId() ?? 0] = $slug->getSlug();
    }

    ksort($bySite);

    return $bySite;
}

function slugField(?string $generator): UrlSlugField
{
    $field = new UrlSlugField();
    $field->setName('slug');
    $field->setSlugGeneratorClass($generator);

    return $field;
}

function localizedFieldsWith(UrlSlugField $field): Localizedfields
{
    $localizedFields = new Localizedfields();
    $localizedFields->setName('localizedfields');
    $localizedFields->addChild($field);

    return $localizedFields;
}
