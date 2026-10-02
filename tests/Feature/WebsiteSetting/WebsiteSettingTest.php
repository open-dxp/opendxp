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
use OpenDxp\Test\Factory\WebsiteSettingFactory;

beforeEach(function () {
    WebsiteSettingFactory::createOne(['name' => 'test', 'data' => 'for anyone']);
    WebsiteSettingFactory::createOne(['name' => 'test', 'data' => 'for site one', 'siteId' => 1]);
    WebsiteSettingFactory::createOne(['name' => 'test', 'data' => 'in english', 'language' => 'en']);
    WebsiteSettingFactory::createOne(['name' => 'test', 'data' => 'in english of site one', 'language' => 'en', 'siteId' => 1]);
});

it('hands back the setting that fits the site and the language best', function (?int $site, ?string $language, string $expected) {
    expect(WebsiteSetting::getByName('test', $site, $language)->getData())->toBe($expected);
})->with([
    'no site and no language' => [null, null, 'for anyone'],
    'a site that has one' => [1, null, 'for site one'],
    'a site and a language that both have one' => [1, 'en', 'in english of site one'],
    'a site that has one and a language that does not' => [1, 'de', 'for site one'],
    'a site that has none' => [2, null, 'for anyone'],
    'a site that has none and a language that has one' => [2, 'en', 'in english'],
    'a site and a language that both have none' => [2, 'de', 'for anyone'],
    'no site and a language that has one' => [null, 'en', 'in english'],
    'no site and a language that has none' => [null, 'de', 'for anyone'],
]);

it('hands back nothing for a name it holds no setting under', function () {
    expect(WebsiteSetting::getByName('test2'))->toBeNull();
});
