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

use OpenDxp\Model\Asset\Image\Thumbnail\Config;

/**
 * @extends AbstractSavingFactory<Config>
 *
 * @method Config create(array|callable $attributes = [])
 * @method static Config createOne(array $attributes = [])
 * @method static list<Config> createMany(int $number, array $attributes = [])
 */
final class ThumbnailConfigFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return Config::class;
    }

    public function scalingByWidth(int $width, bool $forceResize = false): static
    {
        return $this
            ->with(['name' => sprintf('scale-by-width-%d-%s', $width, $forceResize ? 'forced' : 'free')])
            ->transforming('scaleByWidth', ['width' => $width, 'forceResize' => $forceResize]);
    }

    public function rotating(int $angle): static
    {
        return $this
            ->with(['name' => sprintf('rotate-%d', $angle)])
            ->transforming('rotate', ['angle' => $angle]);
    }

    private function transforming(string $transformation, array $parameters): static
    {
        // The default priority puts this before AbstractSavingFactory's save.
        return $this->afterInstantiate(
            static fn (Config $config) => $config->addItem($transformation, $parameters, 'default'),
        );
    }

    protected function defaults(): array
    {
        return [
            'name' => sprintf('thumbnail-%s', self::faker()->unique()->numerify('##########')),
        ];
    }
}
