<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Document\DocType;

/**
 * @extends AbstractSavingFactory<DocType>
 */
final class DocTypeFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return DocType::class;
    }

    /**
     * @param class-string $controller
     */
    public function withController(string $controller, string $action): static
    {
        return $this->with(['controller' => sprintf('%s::%s', $controller, $action)]);
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->unique()->slug(2),
            'type' => 'page',
        ];
    }
}
