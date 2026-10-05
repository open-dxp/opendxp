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

namespace OpenDxp\Tests\Unit\DependencyInjection\Config\Processor;

use OpenDxp\Bundle\CoreBundle\DependencyInjection\Config\Processor\PlaceholderProcessor;

it('puts the placeholders into the configuration', function (array $configuration, array $placeholders, array $expected) {
    expect((new PlaceholderProcessor())->mergePlaceholders($configuration, $placeholders))->toBe($expected);
})->with([
    'one value that is nothing but a placeholder' => [
        ['locale' => '%locale%'],
        ['%locale%' => 'en_US'],
        ['locale' => 'en_US'],
    ],
    'two values, each its own placeholder' => [
        ['locale1' => '%locale1%', 'locale2' => '%locale2%'],
        ['%locale1%' => 'de_AT', '%locale2%' => 'en_US'],
        ['locale1' => 'de_AT', 'locale2' => 'en_US'],
    ],
    'a placeholder in the middle of a sentence' => [
        ['locale' => 'my locale is %locale%'],
        ['%locale%' => 'en_US'],
        ['locale' => 'my locale is en_US'],
    ],
    'placeholders further down the tree' => [
        ['locales' => ['locale' => '%locale2%', 'locales' => ['%locale1%', '%locale2%']]],
        ['%locale1%' => 'de_AT', '%locale2%' => 'en_US'],
        ['locales' => ['locale' => 'en_US', 'locales' => ['de_AT', 'en_US']]],
    ],
    'placeholders in the keys as well' => [
        [
            'locales' => [
                'locale' => '%locale1%',
                'locales' => ['%locale1%', '%locale2%'],
                'locale_%locale1%' => '%locale2%',
            ],
            'mapping' => ['%locale1%' => '%locale2%'],
        ],
        ['%locale1%' => 'de_AT', '%locale2%' => 'en_US'],
        [
            'locales' => [
                'locale' => 'de_AT',
                'locales' => ['de_AT', 'en_US'],
                'locale_de_AT' => 'en_US',
            ],
            'mapping' => ['de_AT' => 'en_US'],
        ],
    ],
]);
