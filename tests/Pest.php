<?php

declare(strict_types=1);

use OpenDxp\Tests\TestCase\CacheTestCase;
use OpenDxp\Tests\TestCase\HttpCacheTestCase;
use OpenDxp\TestFoundation\TestCase;
use Zenstruck\Foundry\Test\Factories;

// Unit tests need no application, so they get no test case.
pest()->extend(TestCase::class)->use(Factories::class)->in('Feature/ClassDefinition', 'Feature/Factory');
pest()->extend(CacheTestCase::class)->use(Factories::class)->in('Feature/Cache');
pest()->extend(HttpCacheTestCase::class)->use(Factories::class)->in('Feature/HttpCache');
