<?php

declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

use OpenDxp\Test\Expectation\Elements;
use OpenDxp\Test\Expectation\Fields;
use OpenDxp\TestFoundation\TestCase;
use OpenDxp\Tests\TestCase\CacheTestCase;
use OpenDxp\Tests\TestCase\HttpCacheTestCase;
use OpenDxp\Tests\TestCase\SchemaTestCase;
use OpenDxp\Tests\TestCase\SearchTestCase;

Elements::register();
Fields::register();

// Unit tests need no application, so they get no test case.
pest()->extend(TestCase::class)->in('Feature/Asset', 'Feature/LazyLoading', 'Feature/Permissions', 'Feature/Site', 'Feature/ClassDefinition', 'Feature/ClassificationStore',
    'Feature/DataObject', 'Feature/DataType', 'Feature/Document', 'Feature/Element', 'Feature/Factory', 'Feature/Glossary', 'Feature/Inheritance', 'Feature/Mail', 'Feature/Messenger',
    'Feature/Notification', 'Feature/Relation', 'Feature/Seo', 'Feature/Tool', 'Feature/Translation', 'Feature/Twig', 'Feature/Version', 'Feature/WebsiteSetting');
pest()->extend(SchemaTestCase::class)->in('Feature/Schema');
pest()->extend(SearchTestCase::class)->in('Feature/Search');
pest()->extend(CacheTestCase::class)->in('Feature/Cache');
pest()->extend(HttpCacheTestCase::class)->in('Feature/HttpCache');
