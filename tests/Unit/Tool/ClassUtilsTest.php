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

use OpenDxp\Tool\ClassUtils;
use SplFileInfo;

it('reads the class name out of a file', function (string $file, string $expected) {
    expect(ClassUtils::findClassName(new SplFileInfo(__DIR__ . '/../../Fixtures/classNames/' . $file)))
        ->toBe($expected);
})->with([
    'a namespace of one part' => ['ClassX.php', 'DummyNamespace\ClassX'],
    'a namespace of two parts' => ['ClassY.php', 'OpenDxp\DummyNamespace\ClassY'],
]);
