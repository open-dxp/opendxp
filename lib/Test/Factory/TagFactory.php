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

use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use OpenDxp\Model\Element\Tag;

/**
 * @extends AbstractSavingFactory<Tag>
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

    public function assignedTo(ElementInterface ...$elements): static
    {
        return $this->afterWriting(
            static function (Tag $tag) use ($elements): void {
                foreach ($elements as $element) {
                    Tag::addTagToElement(
                        Service::getElementType($element),
                        $element->getId(),
                        $tag,
                    );
                }
            },
        );
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->unique()->word(),
        ];
    }
}
