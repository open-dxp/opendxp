<?php

declare(strict_types=1);

namespace OpenDxp\Model\DataObject\ClassDefinition;

use OpenDxp\Model\DataObject\ClassDefinition\Data\UrlSlug;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Site;

final readonly class UrlSlugContext
{
    public function __construct(
        public Concrete $object,
        public UrlSlug $fieldDefinition,
        public ?string $language,
        public ?Site $site,
    ) {
    }
}
