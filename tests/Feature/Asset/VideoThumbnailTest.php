<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Asset;

use OpenDxp\Test\Factory\AssetVideoFactory;
use OpenDxp\Test\Factory\VideoThumbnailConfigFactory;

it('converts a video to the formats of its thumbnail', function () {
    $config = VideoThumbnailConfigFactory::new()
        ->scalingByWidth(120)
        ->create();
    $video = AssetVideoFactory::new()
        ->convertedTo($config->getName())
        ->create();

    $thumbnail = $video->getThumbnail($config->getName());

    expect($thumbnail)
        ->toHaveKey('status', 'finished')
        ->and($thumbnail['formats'])
        ->toHaveKey('mp4');
});
