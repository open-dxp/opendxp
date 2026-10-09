<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Bundle\CustomReportsBundle\Tool\Config;

/**
 * @extends AbstractSavingFactory<Config>
 */
final class CustomReportFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return Config::class;
    }

    /**
     * Reads the rows of the report with an SQL query. The grid and the export show every column of the list.
     *
     * @param list<string> $columns
     */
    public function selecting(string $sql, array $columns): static
    {
        return $this->with([
            'dataSourceConfig' => [
                [
                    'type' => 'sql',
                    'sql' => $sql,
                ],
            ],
            'columnConfiguration' => array_map(
                static fn (string $name): array => [
                    'name' => $name,
                    'label' => '',
                    'display' => true,
                    'export' => true,
                    'order' => true,
                ],
                $columns,
            ),
        ]);
    }

    protected function defaults(): array
    {
        return [
            'name' => str_replace('-', '_', self::faker()->unique()->slug(2)),
            'shareGlobally' => true,
        ];
    }
}
