<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Asset;

use Exception;
use OpenDxp\Test\Factory\AssetImageFactory;

// OpenDXP appends .txt to the filename of a PHP file. A valid filename can grow beyond 255 characters this way.
it('refuses a filename that grows beyond 255 characters', function () {
    $asset = AssetImageFactory::new()
        ->unsaved()
        ->create();
    $asset->setFilename(sprintf('%s.php', str_repeat('a', 250)));

    expect(fn () => $asset->save())->toThrow(Exception::class, sprintf(
        'New asset "%s.php.txt" cannot be saved. The filename has 258 characters. The limit is 255.',
        str_repeat('a', 250),
    ));
});
