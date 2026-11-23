<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Asset\Unknown;

use function Zenstruck\Foundry\lazy;

/**
 * @extends AbstractElementFactory<Unknown>
 */
final class AssetUnknownFactory extends AbstractElementFactory
{
    public static function class(): string
    {
        return Unknown::class;
    }

    public static function fixture(): string
    {
        return dirname(__DIR__) . '/Fixtures/unknown.stp';
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            // OpenDXP keeps a file with the extension stp as an unknown asset, whatever its content.
            'filename' => sprintf('%s.stp', self::faker()->unique()->slug()),
            'data'     => lazy(static fn () => file_get_contents(self::fixture())),
        ];
    }
}
