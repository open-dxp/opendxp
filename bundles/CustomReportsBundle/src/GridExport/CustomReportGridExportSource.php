<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\CustomReportsBundle\GridExport;

use OpenDxp\Bundle\AdminBundle\Attribute\AsGridExportSource;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportColumn;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportQuery;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSourceInterface;
use OpenDxp\Bundle\CustomReportsBundle\Security\CustomReportsPermission;
use OpenDxp\Bundle\CustomReportsBundle\Tool\Config;
use OpenDxp\Bundle\CustomReportsBundle\Tool\ReportDataQuery;
use OpenDxp\Model\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsGridExportSource(name: 'custom-reports', permission: CustomReportsPermission::Reports->value, batchSize: 5000)]
final class CustomReportGridExportSource implements GridExportSourceInterface
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function getColumns(GridExportQuery $query): array
    {
        $columns = [];
        foreach ($this->getExportedColumns($this->getReport($query)) as $column) {
            $label = ($column['label'] ?? '') ?: $column['name'];
            $columns[] = new GridExportColumn(
                $column['name'],
                $this->translator->trans($label, domain: 'admin', locale: $query->language),
            );
        }

        return $columns;
    }

    public function countRows(GridExportQuery $query): int
    {
        return (int) $this->getData($query, 0, 1)['total'];
    }

    public function getRows(GridExportQuery $query, int $offset, int $limit): iterable
    {
        $names = array_column($this->getExportedColumns($this->getReport($query)), 'name');

        foreach ($this->getData($query, $offset, $limit)['data'] as $data) {
            $row = [];
            foreach ($names as $name) {
                $value = $data[$name] ?? null;
                $row[$name] = $value === null ? null : (string) $value;
            }

            yield $row;
        }
    }

    /**
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    private function getData(GridExportQuery $query, int $offset, int $limit): array
    {
        $report = $this->getReport($query);
        $configuration = $report->getDataSourceConfig();
        $dataQuery = ReportDataQuery::fromParameters($query->parameters, $configuration);

        return Config::getAdapter($configuration, $report)->getData(
            $dataQuery->filters,
            $dataQuery->sort,
            $dataQuery->dir,
            $offset,
            $limit,
            array_column($this->getExportedColumns($report), 'name'),
            $dataQuery->drillDownFilters,
        );
    }

    private function getReport(GridExportQuery $query): Config
    {
        $report = Config::getByName((string) ($query->parameters['name'] ?? ''));
        if (!$report instanceof Config) {
            throw new NotFoundHttpException('The report of the grid export does not exist.');
        }

        if (!$report->isAllowedForUser(User::getById($query->userId))) {
            throw new AccessDeniedException('The user may not see the report of the grid export.');
        }

        return $report;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getExportedColumns(Config $report): array
    {
        return array_values(array_filter(
            $report->getColumnConfiguration(),
            static fn (array $column): bool => (bool) ($column['export'] ?? false),
        ));
    }
}
