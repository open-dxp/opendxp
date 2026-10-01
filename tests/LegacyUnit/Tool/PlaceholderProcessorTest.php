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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tests\Unit\Tool;

use OpenDxp\Bundle\CoreBundle\DependencyInjection\Config\Processor\PlaceholderProcessor;
use OpenDxp\Tests\Support\Test\TestCase;

class PlaceholderProcessorTest extends TestCase
{
    private PlaceholderProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new PlaceholderProcessor();
    }

    public function testPlaceholdersAreMergedIntoArrayValues(): void
    {
        $input = [
            'locale' => '%locale%',
        ];

        $expected = [
            'locale' => 'en_US',
        ];

        $placeholders = [
            '%locale%' => 'en_US',
        ];

        $this->assertEquals($expected, $this->processor->mergePlaceholders($input, $placeholders));
    }

    public function testMultiplePlaceholdersAreMergedIntoArrayValues(): void
    {
        $input = [
            'locale1' => '%locale1%',
            'locale2' => '%locale2%',
        ];

        $expected = [
            'locale1' => 'de_AT',
            'locale2' => 'en_US',
        ];

        $placeholders = [
            '%locale1%' => 'de_AT',
            '%locale2%' => 'en_US',
        ];

        $this->assertEquals($expected, $this->processor->mergePlaceholders($input, $placeholders));
    }

    public function testPlaceholdersAreMergedIntoCompositeArrayValues(): void
    {
        $input = [
            'locale' => 'my locale is %locale%',
        ];

        $expected = [
            'locale' => 'my locale is en_US',
        ];

        $placeholders = [
            '%locale%' => 'en_US',
        ];

        $this->assertEquals($expected, $this->processor->mergePlaceholders($input, $placeholders));
    }

    public function testPlaceholdersAreMergedIntoDeepArrayValues(): void
    {
        $input = [
            'locales' => [
                'locale' => '%locale2%',
                'locales' => [
                    '%locale1%',
                    '%locale2%',
                ],
            ],
        ];

        $expected = [
            'locales' => [
                'locale' => 'en_US',
                'locales' => [
                    'de_AT',
                    'en_US',
                ],
            ],
        ];

        $placeholders = [
            '%locale1%' => 'de_AT',
            '%locale2%' => 'en_US',
        ];

        $this->assertEquals($expected, $this->processor->mergePlaceholders($input, $placeholders));
    }

    public function testPlaceholdersAreMergedIntoArrayKeys(): void
    {
        $input = [
            'locales' => [
                'locale' => '%locale1%',
                'locales' => [
                    '%locale1%',
                    '%locale2%',
                ],
                'locale_%locale1%' => '%locale2%',
            ],
            'mapping' => [
                '%locale1%' => '%locale2%',
            ],
        ];

        $expected = [
            'locales' => [
                'locale' => 'de_AT',
                'locales' => [
                    'de_AT',
                    'en_US',
                ],
                'locale_de_AT' => 'en_US',
            ],
            'mapping' => [
                'de_AT' => 'en_US',
            ],
        ];

        $placeholders = [
            '%locale1%' => 'de_AT',
            '%locale2%' => 'en_US',
        ];

        $this->assertEquals($expected, $this->processor->mergePlaceholders($input, $placeholders));
    }
}
