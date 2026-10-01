<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\DataObject\Concrete;

/**
 * @template T of Concrete
 *
 * @extends AbstractElementFactory<T>
 */
abstract class AbstractDataObjectFactory extends AbstractElementFactory
{
    public function unpublished(): static
    {
        return $this->with(['published' => false]);
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'key'       => sprintf('object-%s', self::faker()->unique()->numerify('##########')),
            'published' => true,
        ];
    }
}
