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

namespace OpenDxp\Tests\Feature\Site;

use OpenDxp\Model\Site;
use OpenDxp\Test\Factory\SiteFactory;

it('keeps its custom settings', function () {
    $site = SiteFactory::new()
        ->withCustomSettings(['myBundle' => ['theme' => 'dark']])
        ->create();

    $reloaded = Site::getById($site->getId());

    expect($reloaded->getCustomSettings('myBundle'))->toBe(['theme' => 'dark']);
});

it('returns no custom settings when none were stored', function () {
    $site = SiteFactory::createOne();

    $reloaded = Site::getById($site->getId());

    expect($reloaded->getCustomSettings())->toBe([]);
});

it('is found under its main domain', function () {
    $site = SiteFactory::createOne(['mainDomain' => 'example.com']);

    $found = Site::getByDomain('example.com');

    expect($found->getId())->toBe($site->getId());
});

it('is found under a domain it also answers to', function () {
    $site = SiteFactory::new()
        ->withDomains([
            'alias.example.com',
            'other.example.com',
        ])
        ->create(['mainDomain' => 'example.com']);

    $found = Site::getByDomain('alias.example.com');

    expect($found->getId())->toBe($site->getId());
});

it('is found under a subdomain of a wildcard it answers to', function () {
    $site = SiteFactory::new()
        ->withDomains(['*.example.com'])
        ->create(['mainDomain' => 'example.com']);

    $found = Site::getByDomain('sub.example.com');

    expect($found->getId())->toBe($site->getId());
});

it('is not found under the domain a wildcard sits below', function () {
    SiteFactory::new()
        ->withDomains(['*.wildcard.test'])
        ->create(['mainDomain' => 'main.wildcard.test']);

    $found = Site::getByDomain('wildcard.test');

    expect($found)->toBeNull();
});

it('is not found under a domain no site answers to', function () {
    SiteFactory::createOne(['mainDomain' => 'example.com']);

    $found = Site::getByDomain('does-not-exist.com');

    expect($found)->toBeNull();
});
