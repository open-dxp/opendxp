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

use OpenDxp\Model\Element\Tag;

/**
 * @extends AbstractSavingFactory<Tag>
 *
 * @method Tag create(array|callable $attributes = [])
 * @method static Tag createOne(array $attributes = [])
 * @method static list<Tag> createMany(int $number, array $attributes = [])
 */
final class TagFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return Tag::class;
    }

    public function withParent(Tag $parent): static
    {
        return $this->with(['parentId' => $parent->getId()]);
    }

    protected function defaults(): array
    {
        return [
            'name'     => sprintf('tag-%s', uniqid()),
            'parentId' => 0,
        ];
    }
}
