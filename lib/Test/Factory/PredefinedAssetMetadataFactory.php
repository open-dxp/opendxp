<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Metadata\Predefined;

/**
 * @extends AbstractSavingFactory<Predefined>
 */
final class PredefinedAssetMetadataFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return Predefined::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->unique()->slug(2),
            'type' => 'input',
        ];
    }
}
