<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Asset\Text;

use function Zenstruck\Foundry\lazy;

/**
 * @extends AbstractElementFactory<Text>
 */
final class AssetTextFactory extends AbstractElementFactory
{
    public static function class(): string
    {
        return Text::class;
    }

    public static function fixture(): string
    {
        return dirname(__DIR__) . '/Fixtures/text.csv';
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'filename' => sprintf('%s.csv', self::faker()->unique()->slug()),
            'data'     => lazy(static fn () => file_get_contents(self::fixture())),
        ];
    }
}
