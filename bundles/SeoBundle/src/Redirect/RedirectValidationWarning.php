<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\SeoBundle\Redirect;

/**
 * @internal
 */
final readonly class RedirectValidationWarning
{
    /**
     * @param string                    $message    a translation key
     * @param array<string, string|int> $parameters the values of the placeholders in the message
     */
    public function __construct(
        public string $message,
        public array $parameters = [],
    ) {
    }
}
