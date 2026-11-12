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

namespace OpenDxp\Tests\Feature\SiteDeclaration;

use OpenDxp\Bundle\SeoBundle\SiteDeclaration\Declaration\LlmsTxtDeclaration;
use OpenDxp\Bundle\SeoBundle\SiteDeclaration\SiteDeclarationRegistry;
use OpenDxp\Bundle\SeoBundle\SiteDeclaration\SiteDeclarationRepository;
use OpenDxp\Model\Site;
use OpenDxp\Test\Factory\SiteFactory;
use OpenDxp\TestFoundation\Container;

it('removes the content of a deleted site for every enabled declaration', function () {
    $site = SiteFactory::createOne(['mainDomain' => 'shop.test']);
    $siteId = $site->getId();

    $this->saveContent('ads_txt', 0, 'main.test, 1, DIRECT');
    $this->saveContent('ads_txt', $siteId, 'shop.test, 2, DIRECT');
    $this->saveContent('security_txt', $siteId, 'Contact: mailto:security@shop.test');

    Site::getById($siteId)->delete();

    $registry = Container::get(SiteDeclarationRegistry::class);
    $repository = Container::get(SiteDeclarationRepository::class);

    expect($repository->loadContentsBySiteId($registry->getDeclaration('ads_txt')))
        ->toBe([0 => 'main.test, 1, DIRECT'])
        ->and($repository->loadContentsBySiteId($registry->getDeclaration('security_txt')))
        ->toBe([]);
});

it('removes the content of a deleted site for a disabled declaration', function () {
    $site = SiteFactory::createOne(['mainDomain' => 'shop.test']);
    $siteId = $site->getId();

    $disabledDeclaration = new LlmsTxtDeclaration();
    $repository = Container::get(SiteDeclarationRepository::class);
    $repository->saveContent($disabledDeclaration, $siteId, '# Shop');

    Site::getById($siteId)->delete();

    expect($repository->loadContentsBySiteId($disabledDeclaration))->toBe([]);
});
