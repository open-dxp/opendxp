<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Asset\Video\Thumbnail\Config;

/**
 * @extends AbstractSavingFactory<Config>
 */
final class VideoThumbnailConfigFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return Config::class;
    }

    public function scalingByWidth(int $width): static
    {
        return $this->afterInstantiate(
            static function (Config $config) use ($width): void {
                $config->addItem('scaleByWidth', ['width' => $width]);
            },
        );
    }

    protected function defaults(): array
    {
        return [
            'name'         => self::faker()->unique()->slug(2),
            'audioBitrate' => 128,
            'videoBitrate' => 700,
        ];
    }
}
