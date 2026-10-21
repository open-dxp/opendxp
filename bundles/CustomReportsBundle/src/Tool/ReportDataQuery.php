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

namespace OpenDxp\Bundle\CustomReportsBundle\Tool;

use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use stdClass;

/**
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
