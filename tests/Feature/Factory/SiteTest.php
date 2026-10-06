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

namespace OpenDxp\Tests\Feature\Factory;

use OpenDxp\Model\Document\Page;
use OpenDxp\Model\Site;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\SiteFactory;

it('brings a root document named after its domain', function () {
    $site = SiteFactory::createOne(['mainDomain' => 'test-domain1.test']);

    expect($site->getRootDocument())
        ->toBeInstanceOf(Page::class)
        ->getKey()
        ->toBe('test-domain1-test');
});

it('serves from a root document a test built', function () {
    $root = DocumentPageFactory::createOne();

    $site = SiteFactory::new()
        ->withRoot($root)
        ->create();

    expect($site->getRootId())->toBe($root->getId());
});

it('carries the custom settings a test gives it', function () {
    $site = SiteFactory::new()
        ->withCustomSettings(['i18n' => ['zone' => 'zone1']])
        ->create();

    expect($site->getCustomSettings())->toBe(['i18n' => ['zone' => 'zone1']]);
});

it('carries the further domains a test gives it', function () {
    $site = SiteFactory::new()
        ->withDomains(['www.test-domain3.test'])
        ->create();

    expect($site->getDomains())->toBe(['www.test-domain3.test']);
});

it('gives every site a domain of its own when none is named', function () {
    $sites = SiteFactory::createMany(3);

    $domains = array_map(
        static fn (Site $site): string => $site->getMainDomain(),
        $sites,
    );

    expect(array_unique($domains))->toHaveCount(3);
});

it('carries the error documents a test names', function () {
    $site = SiteFactory::new()
        ->withErrorDocument('/error')
        ->withLocalizedErrorDocuments(['de' => '/de/fehler'])
        ->create();

    expect($site)
        ->getErrorDocument()
        ->toBe('/error')
        ->getLocalizedErrorDocuments()
        ->toBe(['de' => '/de/fehler']);
});

it('writes no root document for a site that was never written', function () {
    $site = SiteFactory::new()
        ->unsaved()
        ->create();

    expect($site->getRootId())->toBeNull();
});
