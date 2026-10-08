<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Application\Video;

use OpenDxp\Video\Adapter\Ffmpeg;

/**
 * Hands out the video filter that the adapter would pass to ffmpeg.
 */
final class MockFfmpeg extends Ffmpeg
{
    /**
     * @return list<string>
     */
    public function videoFilter(): array
    {
        return $this->videoFilter;
    }
}
