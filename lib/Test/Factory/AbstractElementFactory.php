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

use LogicException;
use OpenDxp\Model\Element\AbstractElement;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Note;
use OpenDxp\Model\Element\Service;
use OpenDxp\Model\Element\Tag;
use OpenDxp\Model\Schedule\Task;

/**
 * @template T of AbstractElement
 *
 * @extends AbstractSavingFactory<T>
 */
abstract class AbstractElementFactory extends AbstractSavingFactory
{
    public function withParent(ElementInterface $parent): static
    {
        return $this->with(['parentId' => $parent->getId()]);
    }

    public function withProperty(string $name, string $type, mixed $data): static
    {
        return $this->afterInstantiate(
            static function (ElementInterface $element) use ($name, $type, $data): void {
                $element->setProperty($name, $type, $data);
            },
        );
    }

    public function withInheritableProperty(string $name, string $type, mixed $data): static
    {
        return $this->afterInstantiate(
            static function (ElementInterface $element) use ($name, $type, $data): void {
                $element->setProperty(
                    $name,
                    $type,
                    $data,
                    inheritable: true,
                );
            },
        );
    }

    public function withTags(Tag ...$tags): static
    {
        return $this->afterWriting(
            static function (ElementInterface $element) use ($tags): void {
                foreach ($tags as $tag) {
                    Tag::addTagToElement(
                        Service::getElementType($element),
                        $element->getId(),
                        $tag,
                    );
                }
            },
        );
    }

    public function withNote(Note $note): static
    {
        return $this->afterWriting(
            static function (ElementInterface $element) use ($note): void {
                // Every element a factory creates needs a note of its own.
                $copy = clone $note;
                $copy->setElement($element);
                $copy->save();
            },
        );
    }

    public function withScheduledTask(Task $task): static
    {
        return $this->afterInstantiate(
            static function (ElementInterface $element) use ($task): void {
                if (!method_exists($element, 'setScheduledTasks')) {
                    throw new LogicException(sprintf('A %s has no scheduled tasks.', $element::class));
                }

                // Every element a factory creates needs a task of its own.
                $element->setScheduledTasks([
                    ...$element->getScheduledTasks(),
                    clone $task,
                ]);
            },
        );
    }

    protected function defaults(): array
    {
        return [
            'parentId'         => 1,
            'userOwner'        => 1,
            'userModification' => 1,
        ];
    }
}
