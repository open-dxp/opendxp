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
 */
final class ThumbnailConfigFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return Config::class;
    }

    public function scalingByWidth(int $width): static
    {
        return $this->transforming(
            'scaleByWidth',
            [
                'width'       => $width,
                'forceResize' => false,
            ],
        );
    }

    public function enlargingToWidth(int $width): static
    {
        return $this->transforming(
            'scaleByWidth',
            [
                'width'       => $width,
                'forceResize' => true,
            ],
        );
    }

    /**
     * A cover thumbnail fills the whole box, so it enlarges an image that is too small.
     */
    public function covering(int $width, int $height): static
    {
        return $this->transforming(
            'cover',
            [
                'width'       => $width,
                'height'      => $height,
                'positioning' => 'center',
                'forceResize' => true,
            ],
        );
    }

    public function rotating(int $angle): static
    {
        return $this->transforming(
            'rotate',
            ['angle' => $angle],
        );
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->unique()->slug(2),
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function transforming(string $transformation, array $parameters): static
    {
        return $this->afterInstantiate(
            static function (Config $config) use ($transformation, $parameters): void {
                $config->addItem($transformation, $parameters, 'default');
            },
        );
    }
}
