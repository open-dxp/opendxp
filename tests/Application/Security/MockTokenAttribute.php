<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Application\Security;

/**
 * Reports a deprecation when it is unserialized, like the debug class loader does for a class it loads.
 */
final class MockTokenAttribute
{
    public function __serialize(): array
    {
        return [];
    }

    public function __unserialize(array $data): void
    {
        trigger_error('A class loaded while the token is unserialized is deprecated.', E_USER_DEPRECATED);
    }
}
