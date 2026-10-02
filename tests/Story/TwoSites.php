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
use OpenDxp\Model\Document\Page;
use OpenDxp\Test\Factory\DocumentHardlinkFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\SiteFactory;
use Zenstruck\Foundry\Story;

/**
 * Two sites on domains of their own. The second one carries a hardlink onto a section of the
 * first, so a document of one site can be reached through the other.
 */
final class TwoSites extends Story
{
    public function build(): void
    {
        $this->addState('siteA', SiteFactory::createOne(['mainDomain' => 'domain-a.test']));
        $this->addState('siteB', SiteFactory::createOne(['mainDomain' => 'domain-b.test']));

        $this->addState('section', self::below(self::get('siteA')->getRootDocument(), 'section'));
        $this->addState('subpage', self::below(self::get('section'), 'subpage'));

        $this->addState('hardlink', DocumentHardlinkFactory::createOne([
            'parentId' => self::get('siteB')->getRootDocument()->getId(),
            'key' => 'hl',
            'sourceId' => self::get('section')->getId(),
            'childrenFromSource' => true,
        ]));

        // A site is only found under its domain once the mapping is built again.
        RuntimeCache::getInstance()->offsetUnset('sites_path_mapping');
    }

    private static function below(Page $parent, string $key): Page
    {
        return DocumentPageFactory::createOne(['parentId' => $parent->getId(), 'key' => $key]);
    }
}
