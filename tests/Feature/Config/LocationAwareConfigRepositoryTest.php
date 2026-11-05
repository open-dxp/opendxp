<?php

declare(strict_types=1);

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
