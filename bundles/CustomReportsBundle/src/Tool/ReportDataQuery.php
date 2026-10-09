<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\CustomReportsBundle\Tool;

use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use stdClass;

/**
 * Holds the sorting and the filters of a report grid.
 * Without a sorting of the grid, the sorting of the report applies.
 *
 * @internal
 */
final readonly class ReportDataQuery
{
    /**
     * @param array<int, mixed>|null $filters
     * @param array<int|string, mixed> $drillDownFilters
     */
    public function __construct(
        public ?string $sort,
        public ?string $dir,
        public ?array $filters,
        public array $drillDownFilters,
    ) {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public static function fromParameters(array $parameters, stdClass $configuration): self
    {
        $sort = null;
        $dir = null;
        $sortingSettings = QueryParams::extractSortingSettings($parameters);

        if ($sortingSettings['orderKey']) {
            $sort = $sortingSettings['orderKey'];
            $dir = $sortingSettings['order'];
        } elseif (
            property_exists($configuration, 'orderby') &&
            $configuration->orderby !== '' &&
            $configuration->orderbydir !== ''
        ) {
            $sort = $configuration->orderby;
            $dir = $configuration->orderbydir;
        }

        $filters = isset($parameters['filter']) ? json_decode((string) $parameters['filter'], true) : null;

        return new self(
            sort: $sort,
            dir: $dir,
            filters: is_array($filters) ? $filters : null,
            drillDownFilters: (array) ($parameters['drillDownFilters'] ?? []),
        );
    }
}
