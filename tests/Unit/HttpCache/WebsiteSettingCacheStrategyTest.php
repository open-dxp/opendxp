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

namespace OpenDxp\Tests\Unit\HttpCache;

use OpenDxp\Bundle\CoreBundle\HttpCache\Strategy\WebsiteSettingCacheStrategy;
use OpenDxp\Event\Model\WebsiteSettingLoadEvent;
use OpenDxp\Model\WebsiteSetting;

function websiteSetting(int $id): WebsiteSetting
{
    $setting = new WebsiteSetting();
    $setting->setId($id);

    return $setting;
}

it('tags a website setting', function (WebsiteSettingLoadEvent|WebsiteSetting $subject, array $expected) {
    $strategy = new WebsiteSettingCacheStrategy();

    $tags = $strategy->getTags($subject);

    expect(array_map(strval(...), $tags))->toBe($expected);
})->with([
    'a single setting that was loaded' => [
        fn () => new WebsiteSettingLoadEvent(
            WebsiteSettingLoadEvent::TYPE_SINGLE,
            setting: websiteSetting(5),
        ),
        ['website_setting_5'],
    ],
    'the data of a setting that was loaded' => [
        fn () => new WebsiteSettingLoadEvent(
            WebsiteSettingLoadEvent::TYPE_DATA,
            key: 'featureToggleX',
            id: 5,
        ),
        ['website_setting_5'],
    ],
    'a listing that was loaded' => [
        fn () => new WebsiteSettingLoadEvent(WebsiteSettingLoadEvent::TYPE_LIST, values: []),
        ['website_setting_list'],
    ],
    'a setting that changed' => [
        fn () => websiteSetting(5),
        [
            'website_setting_5',
            'website_setting_list',
        ],
    ],
]);
