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

namespace OpenDxp\Tests\Feature\WebsiteSetting;

use OpenDxp\Model\WebsiteSetting;
use OpenDxp\Test\Factory\SiteFactory;
use OpenDxp\Test\Factory\WebsiteSettingFactory;

beforeEach(function () {
    $this->site = SiteFactory::createOne();
    $this->otherSite = SiteFactory::createOne();

    WebsiteSettingFactory::createOne([
        'name' => 'test',
        'data' => 'for anyone',
    ]);
    WebsiteSettingFactory::new()
        ->forSite($this->site)
        ->create([
            'name' => 'test',
            'data' => 'for the site',
        ]);
    WebsiteSettingFactory::new()
        ->inLanguage('en')
        ->create([
            'name' => 'test',
            'data' => 'in english',
        ]);
    WebsiteSettingFactory::new()
        ->forSite($this->site)
        ->inLanguage('en')
        ->create([
            'name' => 'test',
            'data' => 'in english of the site',
        ]);
});

it('returns the setting that fits site and language best', function (?int $site, ?string $language, string $expected) {
    $setting = WebsiteSetting::getByName('test', $site, $language);

    expect($setting->getData())->toBe($expected);
})->with([
    'no site and no language' => [null, null, 'for anyone'],
    'a site that has one' => [
        fn () => $this->site->getId(),
        null,
        'for the site',
    ],
    'a site and a language that both have one' => [
        fn () => $this->site->getId(),
        'en',
        'in english of the site',
    ],
    'a site that has one and a language that does not' => [
        fn () => $this->site->getId(),
        'de',
        'for the site',
    ],
    'a site that has none' => [
        fn () => $this->otherSite->getId(),
        null,
        'for anyone',
    ],
    'a site that has none and a language that has one' => [
        fn () => $this->otherSite->getId(),
        'en',
        'in english',
    ],
    'a site and a language that both have none' => [
        fn () => $this->otherSite->getId(),
        'de',
        'for anyone',
    ],
    'no site and a language that has one' => [null, 'en', 'in english'],
    'no site and a language that has none' => [null, 'de', 'for anyone'],
]);

it('returns nothing for a name without a setting', function () {
    $setting = WebsiteSetting::getByName('test2');

    expect($setting)->toBeNull();
});
