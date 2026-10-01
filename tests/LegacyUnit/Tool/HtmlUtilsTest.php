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

use OpenDxp\Tests\Support\Test\TestCase;
use OpenDxp\Tool\HtmlUtils;

class HtmlUtilsTest extends TestCase
{
    private array $attributes = [
        'foo' => 'bar',
        'baz' => 'inga',
        'noop' => null,
        'quux' => true,
        'john' => 1,
        'doe' => 2,
    ];

    public function testAssembleAttributeString(): void
    {
        $this->assertEquals(
            'foo="bar" baz="inga" noop quux="1" john="1" doe="2"',
            HtmlUtils::assembleAttributeString($this->attributes)
        );
    }

    public function testAssembleAttributeStringOmitsNullValuesWhenConfigured(): void
    {
        $this->assertEquals(
            'foo="bar" baz="inga" quux="1" john="1" doe="2"',
            HtmlUtils::assembleAttributeString($this->attributes, true)
        );
    }
}
