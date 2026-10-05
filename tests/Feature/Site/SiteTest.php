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

beforeEach(fn () => $this->site = SiteFactory::createOne(['mainDomain' => 'example.com']));

it('hands its custom settings back after a reload', function () {

    $this->site->setCustomSettings(['myBundle' => ['theme' => 'dark']]);
    $this->site->save();

    expect(Site::getById($this->site->getId())->getCustomSettings('myBundle'))->toBe(['theme' => 'dark']);
});

it('carries no custom settings after a reload when they were set to nothing', function () {

    $this->site->setCustomSettings(null);
    $this->site->save();

    expect(Site::getById($this->site->getId())->getCustomSettings())->toBe([]);
});

it('is found under its main domain', function () {
    expect(Site::getByDomain('example.com')?->getId())->toBe($this->site->getId());
});

it('is found under a domain it also answers to', function () {

    $this->site->setDomains(['alias.example.com', 'other.example.com']);
    $this->site->save();

    expect(Site::getByDomain('alias.example.com')?->getId())->toBe($this->site->getId());
});

it('is found under a subdomain of a wildcard it answers to', function () {

    $this->site->setDomains(['*.example.com']);
    $this->site->save();

    expect(Site::getByDomain('sub.example.com')?->getId())->toBe($this->site->getId());
});

it('is not found under the domain a wildcard sits below', function () {

    $site = SiteFactory::createOne(['mainDomain' => 'main.wildcard.test']);
    $site->setDomains(['*.wildcard.test']);
    $site->save();

    expect(Site::getByDomain('wildcard.test'))->toBeNull();
});

it('is not found under a domain no site answers to', function () {
    expect(Site::getByDomain('does-not-exist.com'))->toBeNull();
});
