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
     * @param list<string> $columns
     */
    public function withSqlDataSource(string $sql, array $columns): static
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
