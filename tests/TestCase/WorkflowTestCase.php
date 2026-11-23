<?php

declare(strict_types=1);

namespace OpenDxp\Tests\TestCase;

use OpenDxp\TestFoundation\EnvironmentTestCase;

abstract class WorkflowTestCase extends EnvironmentTestCase
{
    protected static function environment(): string
    {
        return 'workflow';
    }
}
