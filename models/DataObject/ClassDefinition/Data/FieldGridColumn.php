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

namespace OpenDxp\Model\DataObject\ClassDefinition\Data;

use Doctrine\DBAL\Query\QueryBuilder;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\Concrete;

final readonly class FieldGridColumn
{
    public function __construct(
        public Data&GridColumnsInterface $field,
        public string $gridColumn,
        public Data $definition,
    ) {
    }

    /**
     * Returns the key of the grid column like `~classificationstore~teststore~1-2`.
     */
    public function getKey(): string
    {
        return sprintf('~%s~%s~%s', $this->field->getFieldType(), $this->field->getName(), $this->gridColumn);
    }

    public function getName(): string
    {
        return $this->field->getName() . '~' . $this->field->getGridColumnName($this->gridColumn);
    }

    public function getValue(Concrete $object, array $params = []): mixed
    {
        return $this->field->getGridColumnValue($this->gridColumn, $object, $params);
    }

    public function setValue(Concrete $object, mixed $value, array $params = []): void
    {
        $this->field->setGridColumnValue($this->gridColumn, $object, $value, $params);
    }

    public function getForCsvExport(Concrete $object, array $params = []): string
    {
        return $this->field->getGridColumnForCsvExport($this->gridColumn, $object, $params);
    }

    public function addToQuery(
        QueryBuilder $queryBuilder,
        ClassDefinition $class,
        string $objectTable,
        string $alias,
        array $params = [],
    ): void {
        $this->field->addGridColumnToQuery($queryBuilder, $class, $objectTable, $this->gridColumn, $alias, $params);
    }

    public function getFilterCondition(mixed $value, string $operator, string $alias): string
    {
        return $this->field->getGridColumnFilterCondition($this->gridColumn, $value, $operator, $alias);
    }
}
