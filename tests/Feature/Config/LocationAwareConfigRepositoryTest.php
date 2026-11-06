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

use Exception;
use OpenDxp\Config\LocationAwareConfigRepository;

beforeEach(function () {
    $this->directory = sys_get_temp_dir() . '/' . uniqid('opendxp_config_', true);
    mkdir($this->directory . '/thumbnails', recursive: true);

    $this->repository = new LocationAwareConfigRepository([], null, [
        LocationAwareConfigRepository::WRITE_TARGET => [
            LocationAwareConfigRepository::TYPE => LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG,
            LocationAwareConfigRepository::OPTIONS => [LocationAwareConfigRepository::DIRECTORY => $this->directory . '/thumbnails'],
        ],
    ]);
});

it('keeps a configuration inside its directory', function (string $key) {
    expect(fn () => $this->repository->saveConfig($key, ['name' => $key]))
        ->toThrow(Exception::class, sprintf('"%s" is not a valid configuration key.', $key));

    expect(glob($this->directory . '/*.yaml'))->toBe([]);
})->with(['../escape', '..\\escape', 'sub/escape', '..']);
