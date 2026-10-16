<?php

declare(strict_types=1);

namespace OpenDxp\Model\DataObject\ClassDefinition\Data;

use Doctrine\DBAL\Query\QueryBuilder;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\Concrete;

interface GridColumnsInterface
{
    public function hasGridColumn(string $gridColumn): bool;

    public function getGridColumnDefinition(string $gridColumn): ?Data;

    public function getGridColumnName(string $gridColumn): string;

    public function getGridColumnValue(string $gridColumn, Concrete $object, array $params = []): mixed;

    public function setGridColumnValue(string $gridColumn, Concrete $object, mixed $value, array $params = []): void;

    public function getGridColumnForCsvExport(string $gridColumn, Concrete $object, array $params = []): string;

    public function addGridColumnToQuery(
        QueryBuilder $queryBuilder,
        ClassDefinition $class,
        string $objectTable,
        string $gridColumn,
        string $alias,
        array $params = [],
    ): void;

    public function getGridColumnFilterCondition(
        string $gridColumn,
        mixed $value,
        string $operator,
        string $alias,
    ): string;
}
