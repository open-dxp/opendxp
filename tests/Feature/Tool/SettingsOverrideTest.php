<?php

declare(strict_types=1);

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
