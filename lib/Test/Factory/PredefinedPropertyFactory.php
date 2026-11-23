<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Property\Predefined;

/**
 * @extends AbstractSavingFactory<Predefined>
 */
final class PredefinedPropertyFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return Predefined::class;
    }

    protected function defaults(): array
    {
        return [
            'name'  => self::faker()->slug(2),
            'key'   => self::faker()->unique()->slug(2),
            'type'  => 'text',
            'ctype' => 'document',
        ];
    }
}
