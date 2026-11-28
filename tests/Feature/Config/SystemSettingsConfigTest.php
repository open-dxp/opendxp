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

namespace OpenDxp\Tests\Feature\Config;

use OpenDxp;
use OpenDxp\SystemSettingsConfig;
use OpenDxp\Tests\Application\TestKernel;
use OpenDxp\Tool;

it('reads the fallback languages of a kernel booted after another one', function () {
    expect(Tool::getFallbackLanguagesFor('de'))->toBe(['en']);

    $kernel = new TestKernel('languages', true);
    $kernel->boot();

    try {
        expect(Tool::getFallbackLanguagesFor('de'))->toBe([]);
    } finally {
        $kernel->shutdown();
        OpenDxp::setKernel(self::$kernel);
        self::$kernel->getContainer()->get(SystemSettingsConfig::class)->reset();
    }
});
