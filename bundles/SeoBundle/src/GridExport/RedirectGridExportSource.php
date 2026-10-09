<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\SeoBundle\GridExport;

use OpenDxp\Bundle\AdminBundle\Attribute\AsGridExportSource;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportColumn;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportQuery;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSourceInterface;
use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Bundle\SeoBundle\Redirect\Csv;
use OpenDxp\Bundle\SeoBundle\Redirect\RedirectGridListingFactory;
use OpenDxp\Model\User;
use OpenDxp\Security\CorePermission;

#[AsGridExportSource(name: 'redirects', permission: CorePermission::Redirects->value)]
final class RedirectGridExportSource implements GridExportSourceInterface
{
    public function __construct(
        private readonly RedirectGridListingFactory $listingFactory,
        private readonly Csv $csv,
    ) {
    }

    public function getColumns(GridExportQuery $query): array
    {
        // The import finds a column by its title, so every title is the key of its column.
        return array_map(
            static fn (string $key): GridExportColumn => new GridExportColumn($key, $key),
            $this->csv->getExportColumns(),
        );
    }

    public function countRows(GridExportQuery $query): int
    {
        return $this->createListing($query)->getTotalCount();
    }

    public function getRows(GridExportQuery $query, int $offset, int $limit): iterable
    {
        $listing = $this->createListing($query);
        $listing->setOffset($offset);
        $listing->setLimit($limit);

        foreach ($listing->getRedirects() as $redirect) {
            yield array_combine(
                $this->csv->getExportColumns(),
                array_map($this->formatValue(...), $this->csv->createExportRecord($redirect)),
            );
        }
    }

    private function createListing(GridExportQuery $query): Redirect\Listing
    {
        $listing = $this->listingFactory->create(
            $query->parameters,
            (bool) User::getById($query->userId)?->isAllowed('redirects_protected'),
        );

        if ($query->selectedIds !== []) {
            $listing->addConditionParam('id IN (?)', [array_map(intval(...), $query->selectedIds)]);
        }

        return $listing;
    }

    /**
     * Formats a value the way the CSV writer of the import format does: true as "1" and false as an empty string.
     */
    private function formatValue(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            is_bool($value) => $value ? '1' : '',
            default => (string) $value,
        };
    }
}
