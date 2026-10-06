<?php

declare(strict_types=1);

namespace OpenDxp\Tests\TestCase;

use DAMA\DoctrineTestBundle\PHPUnit\SkipDatabaseRollback;
use OpenDxp\TestFoundation\EnvironmentTestCase;

// DDL commits the transaction, so a test that changes the schema cannot be rolled back.
#[SkipDatabaseRollback]
abstract class InstallerTestCase extends EnvironmentTestCase
{
    protected static function environment(): string
    {
        return 'installer';
    }
}
