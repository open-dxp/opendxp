<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\SeoBundle\Redirect;

/**
 * @internal
 */
final readonly class RedirectValidationError
{
    /**
     * @param string $message a translation key
     */
    public function __construct(
        public string $field,
        public string $message,
    ) {
    }
}
