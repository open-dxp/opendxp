<?php

declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

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
     * Foundry runs the hooks from the highest priority down. Writing this late lets every state change the model first.
     */
    protected const int WRITE = -1000;

    /**
     * A hook at this priority runs after the write, so the model already has its id.
     */
    protected const int AFTER_WRITE = -2000;

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
                    // Not every model declares save(). Several reach their dao through __call.
                    $model->save();
                }
            },
            self::WRITE,
        );
    }

    /**
     * @param callable(T): void $hook
     */
    protected function afterWriting(callable $hook): static
    {
        return $this->afterInstantiate(
            static function (AbstractModel $model, array $parameters, self $factory) use ($hook): void {
                if ($factory->writes) {
                    $hook($model);
                }
            },
            self::AFTER_WRITE,
        );
    }

    protected function writes(): bool
    {
        return $this->writes;
    }
}
