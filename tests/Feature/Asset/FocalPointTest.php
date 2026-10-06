<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Asset;

it('crops a cover thumbnail around a focal point on the left edge', function () {
    $image = redAboveBlue();
    focusOn($image, 0.0, 90.0);

    expect(thumbnailColours($image, landscapeCover()))
        ->toBe('blue above blue');
});

it('crops the thumbnails again once the focal point moves', function () {
    $image = redAboveBlue();
    $cover = landscapeCover();
    focusOn($image, 50.0, 90.0);
    thumbnailColours($image, $cover);

    focusOn($image, 50.0, 10.0);

    expect(thumbnailColours($image, $cover))
        ->toBe('red above red');
});

it('crops the thumbnails around the middle again once the focal point is removed', function () {
    $image = redAboveBlue();
    $cover = landscapeCover();
    focusOn($image, 50.0, 90.0);
    thumbnailColours($image, $cover);

    $image->removeCustomSetting('focalPointX');
    $image->removeCustomSetting('focalPointY');
    $image->save();

    expect(thumbnailColours($image, $cover))
        ->toBe('red above blue');
});
