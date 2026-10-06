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

use OpenDxp\Test\Expectation\Fields;
use OpenDxp\Test\Expectation\Redirects;
use OpenDxp\TestFoundation\TestCase;
use OpenDxp\Tests\TestCase\CacheTestCase;
use OpenDxp\Tests\TestCase\HttpCacheTestCase;
use OpenDxp\Tests\TestCase\InstallerTestCase;
use OpenDxp\Tests\TestCase\SchemaTestCase;
use OpenDxp\Tests\TestCase\SearchTestCase;

Fields::register();
Redirects::register();

// Unit tests need no application, so they get no test case.
pest()->extend(TestCase::class)->in(
    'Feature/Asset',
    'Feature/ClassDefinition',
    'Feature/ClassificationStore',
    'Feature/Console',
    'Feature/DataObject',
    'Feature/DataType',
    'Feature/Dependency',
    'Feature/Document',
    'Feature/Element',
    'Feature/Factory',
    'Feature/Glossary',
    'Feature/Inheritance',
    'Feature/LazyLoading',
    'Feature/Mail',
    'Feature/Messenger',
    'Feature/Notification',
    'Feature/Permissions',
    'Feature/Relation',
    'Feature/Seo',
    'Feature/Site',
    'Feature/Tool',
    'Feature/Translation',
    'Feature/Twig',
    'Feature/Version',
    'Feature/WebsiteSetting',
);
pest()->extend(CacheTestCase::class)->in('Feature/Cache');
pest()->extend(HttpCacheTestCase::class)->in('Feature/HttpCache');
pest()->extend(InstallerTestCase::class)->in('Feature/Installer');
pest()->extend(SchemaTestCase::class)->in('Feature/Schema');
pest()->extend(SearchTestCase::class)->in('Feature/Search');
