<?php

declare(strict_types=1);

use OpenDxp\TestFoundation\TestCase;
use Zenstruck\Foundry\Test\Factories;

// Unit tests need no application, so they get no test case.
pest()->extend(TestCase::class)->use(Factories::class)->in('Feature');
