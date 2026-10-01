<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Bundle\StaticRoutesBundle\Model\Staticroute;

/**
 * @extends AbstractSavingFactory<Staticroute>
 *
 * @method Staticroute create(array|callable $attributes = [])
 * @method static Staticroute createOne(array $attributes = [])
 * @method static list<Staticroute> createMany(int $number, array $attributes = [])
 */
final class StaticRouteFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return Staticroute::class;
    }

    /**
     * @param class-string $controller
     */
    public function withController(string $controller, string $action): static
    {
        return $this->with(['controller' => sprintf('%s::%s', $controller, $action)]);
    }

    public function withPattern(string $pattern, string $reverse): static
    {
        return $this->with(['pattern' => $pattern, 'reverse' => $reverse]);
    }

    protected function defaults(): array
    {
        return [
            'name'     => sprintf('route_%s', self::faker()->unique()->numerify('##########')),
            'priority' => 0,
            'siteId'   => [],
        ];
    }
}
