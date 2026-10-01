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

use OpenDxp\Model\Asset\Video;

/**
 * @extends AbstractElementFactory<Video>
 *
 * @method Video create(array|callable $attributes = [])
 * @method static Video createOne(array $attributes = [])
 * @method static list<Video> createMany(int $number, array $attributes = [])
 */
final class AssetVideoFactory extends AbstractElementFactory
{
    public static function class(): string
    {
        return Video::class;
    }

    public static function fixture(): string
    {
        return dirname(__DIR__) . '/Fixtures/video.mp4';
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'type'     => 'video',
            'filename' => sprintf('video-%s.mp4', self::faker()->unique()->numerify('##########')),
            'data'     => file_get_contents(self::fixture()),
        ];
    }
}
