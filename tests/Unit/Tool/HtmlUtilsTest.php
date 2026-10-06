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

beforeEach(fn () => $this->attributes = [
    'foo' => 'bar',
    'noop' => null,
    'quux' => true,
    'john' => 1,
]);

it('joins the attributes into a string', function () {
    $attributes = HtmlUtils::assembleAttributeString($this->attributes);

    expect($attributes)->toBe('foo="bar" noop quux="1" john="1"');
});

it('leaves out an attribute without a value when asked to', function () {
    $attributes = HtmlUtils::assembleAttributeString($this->attributes, omitNullValues: true);

    expect($attributes)->toBe('foo="bar" quux="1" john="1"');
});
