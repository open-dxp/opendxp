<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\AbstractModel;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @template T of AbstractModel
 *
 * @extends ObjectFactory<T>
 */
abstract class AbstractSavingFactory extends ObjectFactory
{
    /**
     * Foundry runs the hooks from the highest priority down, so writing last lets a state still
     * change the object.
     */
    private const int WRITE = -1000;

    private bool $writes = true;

    public function unsaved(): static
    {
        $clone = clone $this;
        $clone->writes = false;

        return $clone;
    }

    protected function initialize(): static
    {
        return $this->afterInstantiate(
            static function (AbstractModel $model, array $parameters, self $factory): void {
                if ($factory->writes) {
                    // Not every model declares save(), several reach their dao through __call.
                    $model->save();
                }
            },
            self::WRITE,
        );
    }
}
