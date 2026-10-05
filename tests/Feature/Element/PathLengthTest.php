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

namespace OpenDxp\Tests\Feature\Element;

use Exception;

const GREATEST_PATH_LENGTH = 765;

it('accepts a path of the greatest allowed length', function () {
    validatePathLength(elementWithPathLength(GREATEST_PATH_LENGTH));
})->throwsNoExceptions();

it('refuses a path longer than that', function () {
    validatePathLength(elementWithPathLength(GREATEST_PATH_LENGTH + 1));
})->throws(Exception::class);
