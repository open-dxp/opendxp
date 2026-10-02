<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Asset\Image;

/**
 * @extends AbstractElementFactory<Image>
 *
 * @method Image create(array|callable $attributes = [])
 * @method static Image createOne(array $attributes = [])
 * @method static list<Image> createMany(int $number, array $attributes = [])
 */
final class AssetImageFactory extends AbstractElementFactory
{
    public static function class(): string
    {
        return Image::class;
    }

    public static function fixture(string $name = 'image.jpg'): string
    {
        return sprintf('%s/Fixtures/%s', dirname(__DIR__), $name);
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'type'     => 'image',
            'filename' => sprintf('image-%s.jpg', uniqid()),
            'data'     => file_get_contents(self::fixture()),
        ];
    }
}
