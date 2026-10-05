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
