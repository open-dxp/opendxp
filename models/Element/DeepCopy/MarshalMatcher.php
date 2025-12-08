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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Model\Element\DeepCopy;

use DeepCopy\TypeMatcher\TypeMatcher;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;

/**
 * @internal
 */
class MarshalMatcher extends TypeMatcher
{
    /**
     * MarshalMatcher constructor.
     *
     */
    public function __construct(private readonly ?string $sourceType, private readonly ?int $sourceId)
    {
    }

    /**
     * @param mixed $element
     *
     */
    #[\Override]
    public function matches($element): bool
    {
        if ($element instanceof ElementInterface) {
            $elementType = Service::getElementType($element);
            return !($elementType === $this->sourceType && $element->getId() === $this->sourceId);
        }

        return false;
    }
}
