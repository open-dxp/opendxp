<?php

declare(strict_types=1);

use OpenDxp\Test\Expectation\Elements;
use OpenDxp\Test\Expectation\Fields;
use OpenDxp\Tests\TestCase\CacheTestCase;
use OpenDxp\Tests\TestCase\HttpCacheTestCase;
use OpenDxp\Tests\TestCase\SchemaTestCase;
use OpenDxp\Tests\TestCase\SearchTestCase;
use OpenDxp\TestFoundation\TestCase;
use Zenstruck\Foundry\Test\Factories;

Elements::register();
Fields::register();

// Unit tests need no application, so they get no test case.
pest()->extend(TestCase::class)->use(Factories::class)->in('Feature/Asset', 'Feature/LazyLoading', 'Feature/Permissions', 'Feature/Site', 'Feature/ClassDefinition', 'Feature/ClassificationStore',
    'Feature/DataObject', 'Feature/DataType', 'Feature/Document', 'Feature/Element', 'Feature/Factory', 'Feature/Glossary', 'Feature/Inheritance', 'Feature/Mail', 'Feature/Messenger',
    'Feature/Notification', 'Feature/Relation', 'Feature/Tool', 'Feature/Translation', 'Feature/Twig', 'Feature/Version', 'Feature/WebsiteSetting');
pest()->extend(SchemaTestCase::class)->use(Factories::class)->in('Feature/Schema');
pest()->extend(SearchTestCase::class)->use(Factories::class)->in('Feature/Search');
pest()->extend(CacheTestCase::class)->use(Factories::class)->in('Feature/Cache');
pest()->extend(HttpCacheTestCase::class)->use(Factories::class)->in('Feature/HttpCache');
