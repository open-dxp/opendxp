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

namespace OpenDxp\Workflow\MarkingStore;

use OpenDxp\Model\DataObject\Concrete;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;

class DataObjectMultipleStateMarkingStore implements MarkingStoreInterface
{
    private readonly \Symfony\Component\PropertyAccess\PropertyAccessor|PropertyAccessorInterface $propertyAccessor;

    public function __construct(private readonly string $property = 'marking', ?PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
    }

    public function getMarking(object $subject): Marking
    {
        $this->checkIfSubjectIsValid($subject);

        $marking = (array) $this->propertyAccessor->getValue($subject, $this->property);

        $_marking = [];
        foreach ($marking as $place) {
            $_marking[$place] = 1;
        }

        return new Marking($_marking);
    }

    public function setMarking(object $subject, Marking $marking, array $context = []): void
    {
        $subject = $this->checkIfSubjectIsValid($subject);

        $places = array_keys($marking->getPlaces());
        $this->propertyAccessor->setValue($subject, $this->property, $places);
    }

    /**
     * @throws LogicException
     */
    private function checkIfSubjectIsValid(object $subject): Concrete
    {
        if (!$subject instanceof Concrete) {
            throw new LogicException('data_object_multiple_state marking store works for opendxp data objects only.');
        }

        return $subject;
    }
}
