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

namespace OpenDxp\Tests\Unit\Seo;

use LogicException;
use OpenDxp\Bundle\SeoBundle\SiteDeclaration\SiteDeclarationRegistry;
use OpenDxp\Tests\Application\SiteDeclaration\MockSiteDeclaration;

it('returns the enabled declarations only', function () {
    $registry = new SiteDeclarationRegistry(
        [
            new MockSiteDeclaration('robots_txt', '/robots.txt'),
            new MockSiteDeclaration('llms_txt', '/llms.txt'),
        ],
        ['llms_txt'],
    );

    $names = array_keys($registry->getDeclarations());

    expect($names)->toBe(['llms_txt']);
});

it('refuses a broken registration', function (array $declarations, array $enabledNames, string $message) {
    $registry = new SiteDeclarationRegistry($declarations, $enabledNames);

    expect(fn () => $registry->getDeclarations())->toThrow(LogicException::class, $message);
})->with([
    'two declarations with the same name' => [
        fn () => [
            new MockSiteDeclaration('ads_txt', '/ads.txt'),
            new MockSiteDeclaration('ads_txt', '/other.txt'),
        ],
        [],
        'both use the name "ads_txt"',
    ],
    'a name that is not snake case' => [
        fn () => [new MockSiteDeclaration('ads.txt', '/ads.txt')],
        [],
        'A name is snake case',
    ],
    'a name with more than 33 characters' => [
        fn () => [new MockSiteDeclaration(str_repeat('a', 34), '/a.txt')],
        [],
        'at most 33 characters',
    ],
    'an enabled name without a declaration' => [
        fn () => [new MockSiteDeclaration('ads_txt', '/ads.txt')],
        ['securty_txt'],
        'No site declaration has the name "securty_txt"',
    ],
    'two enabled declarations with the same path' => [
        fn () => [
            new MockSiteDeclaration('ads_txt', '/ads.txt'),
            new MockSiteDeclaration('other_ads_txt', '/ads.txt'),
        ],
        [
            'ads_txt',
            'other_ads_txt',
        ],
        'both use the path /ads.txt',
    ],
]);
