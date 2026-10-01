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
use OpenDxp\Tests\Support\Test\TestCase;

class WebsiteSettingCacheStrategyTest extends TestCase
{
    private WebsiteSettingCacheStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->strategy = new WebsiteSettingCacheStrategy();
    }

    public function testSingleLoadOnlyTagsTheSetting(): void
    {
        $setting = new WebsiteSetting();
        $setting->setId(5);

        $event = new WebsiteSettingLoadEvent(WebsiteSettingLoadEvent::TYPE_SINGLE, setting: $setting);
        $tags  = array_map(strval(...), $this->strategy->getTags($event));

        $this->assertSame(['website_setting_5'], $tags);
    }

    public function testDataLoadOnlyTagsTheSetting(): void
    {
        $event = new WebsiteSettingLoadEvent(WebsiteSettingLoadEvent::TYPE_DATA, key: 'featureToggleX', id: 5);
        $tags  = array_map(strval(...), $this->strategy->getTags($event));

        $this->assertSame(['website_setting_5'], $tags);
    }

    public function testListLoadOnlyTagsTheList(): void
    {
        $event = new WebsiteSettingLoadEvent(WebsiteSettingLoadEvent::TYPE_LIST, values: []);
        $tags  = array_map(strval(...), $this->strategy->getTags($event));

        $this->assertSame(['website_setting_list'], $tags);
    }

    public function testChangeInvalidatesBothTheSettingAndTheList(): void
    {
        $setting = new WebsiteSetting();
        $setting->setId(5);

        $tags = array_map(strval(...), $this->strategy->getTags($setting));

        $this->assertSame(['website_setting_5', 'website_setting_list'], $tags);
    }
}
