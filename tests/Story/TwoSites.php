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

namespace OpenDxp\Tests\Story;

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\Document\Hardlink;
use OpenDxp\Model\Document\Page;
use OpenDxp\Model\Site;
use OpenDxp\Test\Factory\DocumentHardlinkFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\SiteFactory;
use Zenstruck\Foundry\Story;

/**
 * @method static Site siteA()
 * @method static Site siteB()
 * @method static Page section()
 * @method static Page subpage()
 * @method static Hardlink hardlink()
 */
final class TwoSites extends Story
{
    public function build(): void
    {
        $siteA = SiteFactory::createOne(['mainDomain' => 'domain-a.test']);
        $siteB = SiteFactory::createOne(['mainDomain' => 'domain-b.test']);

        $section = DocumentPageFactory::new()
            ->withParent($siteA->getRootDocument())
            ->create(['key' => 'section']);
        $subpage = DocumentPageFactory::new()
            ->withParent($section)
            ->create(['key' => 'subpage']);

        // The hardlink lets a document of the first site be reached through the second.
        $hardlink = DocumentHardlinkFactory::new()
            ->withParent($siteB->getRootDocument())
            ->withSource($section)
            ->create(['key' => 'hardlink']);

        $this->addState('siteA', $siteA);
        $this->addState('siteB', $siteB);
        $this->addState('section', $section);
        $this->addState('subpage', $subpage);
        $this->addState('hardlink', $hardlink);

        // A site is only found under its domain once the mapping is built again.
        RuntimeCache::getInstance()->offsetUnset('sites_path_mapping');
    }
}
