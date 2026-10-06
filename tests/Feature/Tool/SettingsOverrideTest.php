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

namespace OpenDxp\Tests\Feature\Tool;

use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Settings;

it('applies a system setting a test overrides', function () {
    $asset = AssetImageFactory::createOne();
    Settings::override([
        'assets' => [
            'frontend_prefixes' => [
                'source' => 'https://cdn.example.test',
            ],
        ],
    ]);

    $path = $asset->getFullPath();

    expect($path)->toBe(sprintf('https://cdn.example.test%s', $asset->getRealFullPath()));
});
