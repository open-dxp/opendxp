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

namespace OpenDxp\Model\DataObject\ClassDefinition\Data;

use OpenDxp\Model;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Concrete;

class Time extends Model\DataObject\ClassDefinition\Data\Input
{
    /**
     * Column length
     *
     * @internal
     *
     */
    public int $columnLength = 5;

    /**
     * @internal
     *
     */
    public ?string $minValue = null;

    /**
     * @internal
     *
     */
    public ?string $maxValue = null;

    /**
     * @internal
     *
     */
    public int $increment = 15 ;

    public function getMinValue(): ?string
    {
        return $this->minValue;
    }

    public function setMinValue(?string $minValue): void
    {
        $this->minValue = is_string($minValue) && strlen($minValue) ? $this->toTime($minValue) : null;
    }

    public function getMaxValue(): ?string
    {
        return $this->maxValue;
    }

    public function setMaxValue(?string $maxValue): void
    {
        $this->maxValue = is_string($maxValue) && strlen($maxValue) ? $this->toTime($maxValue) : null;
    }

    #[\Override]
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        parent::checkValidity($data, $omitMandatoryCheck);

        if (is_string($data)) {
            if (!preg_match('/^(2[0-3]|[01]\d):[0-5]\d$/', $data) && $data !== '') {
                throw new Model\Element\ValidationException('Wrong time format given must be a 5 digit string (eg: 06:49) [ '.$this->getName().' ]');
            }
        } elseif (!empty($data)) {
            throw new Model\Element\ValidationException('Wrong time format given must be a 5 digit string (eg: 06:49) [ '.$this->getName().' ]');
        }

        if (!$omitMandatoryCheck && $data) {
            if (!$this->toTime($data)) {
                throw new Model\Element\ValidationException('Wrong time format given must be a 5 digit string (eg: 06:49) [ '.$this->getName().' ]');
            }

            if ($this->getMinValue() && $this->isEarlier($this->getMinValue(), $data)) {
                throw new Model\Element\ValidationException('Value in field [ '.$this->getName().' ] is not at least ' . $this->getMinValue());
            }

            if ($this->getMaxValue() && $this->isLater($this->getMaxValue(), $data)) {
                throw new Model\Element\ValidationException('Value in field [ ' . $this->getName() . ' ] is bigger than ' . $this->getMaxValue());
            }
        }
    }

    #[\Override]
    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    #[\Override]
    public function isEmpty(mixed $data): bool
    {
        return !is_string($data) || !preg_match('/^(2[0-3]|[01]\d):[0-5]\d$/', $data);
    }

    /**
     * Returns a 5 digit time string of a given time
     *
     *
     */
    private function toTime(string $timestamp): ?string
    {
        $timestamp = strtotime($timestamp);
        if (!$timestamp) {
            return null;
        }

        return date('H:i', $timestamp);
    }

    /**
     * Returns a timestamp representation of a given time
     *
     *
     */
    private function toTimestamp(string $string, ?int $baseTimestamp = null): int
    {
        if ($baseTimestamp === null) {
            $baseTimestamp = time();
        }

        return strtotime($string, $baseTimestamp);
    }

    /**
     * Returns whether or not a time is earlier than the subject
     *
     *
     */
    private function isEarlier(string $subject, string $comparison): bool
    {
        $baseTs = time();

        return $this->toTimestamp($subject, $baseTs) > $this->toTimestamp($comparison, $baseTs);
    }

    /**
     * Returns whether or not a time is later than the subject
     *
     *
     */
    private function isLater(string $subject, string $comparison): bool
    {
        $baseTs = time();

        return $this->toTimestamp($subject, $baseTs) < $this->toTimestamp($comparison, $baseTs);
    }

    #[\Override]
    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        return '';
    }

    public function getIncrement(): int
    {
        return $this->increment;
    }

    public function setIncrement(int $increment): void
    {
        $this->increment = $increment;
    }

    #[\Override]
    public function getFieldType(): string
    {
        return 'time';
    }
}
