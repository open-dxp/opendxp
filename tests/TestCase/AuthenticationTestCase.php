<?php

declare(strict_types=1);

namespace OpenDxp\Tests\TestCase;

use OpenDxp\TestFoundation\EnvironmentTestCase;

/**
 * Allows MockTokenAttribute in the admin session token.
 */
abstract class AuthenticationTestCase extends EnvironmentTestCase
{
    protected static function environment(): string
    {
        return 'authentication';
    }
}
