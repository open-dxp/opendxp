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


namespace OpenDxp\Tests\Unit\Tool;

use OpenDxp\Tool\HtmlUtils;

const ATTRIBUTES = [
    'foo' => 'bar',
    'baz' => 'inga',
    'noop' => null,
    'quux' => true,
    'john' => 1,
    'doe' => 2,
];

it('writes the attributes as a string', function () {
    expect(HtmlUtils::assembleAttributeString(ATTRIBUTES))
        ->toBe('foo="bar" baz="inga" noop quux="1" john="1" doe="2"');
});

it('leaves an attribute without a value out when asked to', function () {
    expect(HtmlUtils::assembleAttributeString(ATTRIBUTES, true))
        ->toBe('foo="bar" baz="inga" quux="1" john="1" doe="2"');
});
