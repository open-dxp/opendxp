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
            'name'     => sprintf('route_%s', uniqid()),
            'priority' => 0,
            'siteId'   => [],
        ];
    }
}
