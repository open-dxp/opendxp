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


namespace OpenDxp\Tests\Feature\Tool;

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Test\Factory\PageFactory;
use OpenDxp\Test\Factory\SiteFactory;
use OpenDxp\Tool\Text;

it('rewrites a link to a document into the url of the site it belongs to', function () {

    $site = SiteFactory::createOne(['mainDomain' => 'example2.com']);
    $page = PageFactory::createOne(['key' => 'testing', 'parentId' => $site->getRootDocument()->getId()]);
    RuntimeCache::clear();

    $text = sprintf(
        'Link to a document <a href="%s" opendxp_id="%s" opendxp_type="document">The link</a>',
        $page->getFullPath(),
        $page->getId(),
    );

    expect(Text::wysiwygText($text))->toBe(sprintf(
        'Link to a document <a href="http://example2.com/testing" opendxp_id="%s" opendxp_type="document">The link</a>',
        $page->getId(),
    ));
});
