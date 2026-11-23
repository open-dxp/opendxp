<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Asset\Audio;

use function Zenstruck\Foundry\lazy;

/**
 * @extends AbstractElementFactory<Audio>
 */
final class AssetAudioFactory extends AbstractElementFactory
{
    public static function class(): string
    {
        return Audio::class;
    }

    public static function fixture(): string
    {
        return dirname(__DIR__) . '/Fixtures/audio.mp3';
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'filename' => sprintf('%s.mp3', self::faker()->unique()->slug()),
            'data'     => lazy(static fn () => file_get_contents(self::fixture())),
        ];
    }
}
