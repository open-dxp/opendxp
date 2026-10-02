<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Element\AbstractElement;
use OpenDxp\Model\Element\ElementInterface;

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

    protected function defaults(): array
    {
        return [
            'parentId'         => 1,
            'userOwner'        => 1,
            'userModification' => 1,
        ];
    }
}
