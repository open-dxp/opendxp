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

namespace OpenDxp\Tests\Feature\Installer;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\AvailableMigration;
use OpenDxp\Db;
use OpenDxp\Migrations\FilteredMigrationsRepository;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\Application\InstallerBundle\Migrations\Version20260101000000;
use OpenDxp\Tests\Application\InstallerBundle\Migrations\Version20260201000000;

/**
 * @return list<string>
 */
function executedTestMigrations(): array
{
    return Db::get()->fetchFirstColumn(
        'SELECT version FROM migration_versions WHERE version LIKE ? ORDER BY version',
        ['OpenDxp\\\\Tests\\\\Application\\\\InstallerBundle%'],
    );
}

function executedAt(string $version): string
{
    return Db::get()->fetchOne(
        'SELECT executed_at FROM migration_versions WHERE version = ?',
        [$version],
    );
}

/**
 * @return list<string>
 */
function availableMigrations(): array
{
    $repository = Container::get(DependencyFactory::class)->getMigrationRepository();

    return array_map(
        static fn (AvailableMigration $migration): string => (string) $migration->getVersion(),
        $repository->getMigrations()->getItems(),
    );
}

it('marks every migration of the bundle as executed when it installs', function () {
    $installer = $this->migrationInstaller();

    $installer->install();

    expect(executedTestMigrations())
        ->toBe([
            Version20260101000000::class,
            Version20260201000000::class,
        ])
        ->and($installer->isInstalled())
        ->toBeTrue();
});

it('skips the migrations of a namespace that only starts like the bundle', function () {
    $installer = $this->migrationInstaller();

    $installer->install();

    expect(executedTestMigrations())
        ->not->toContain('OpenDxp\\Tests\\Application\\InstallerBundleExtension\\Migrations\\Version20260301000000');
});

it('keeps a migration that already ran', function () {
    Db::get()->insert('migration_versions', [
        'version' => Version20260101000000::class,
        'executed_at' => '2026-01-01 00:00:00',
    ]);
    $installer = $this->migrationInstaller();

    $installer->install();

    expect(executedAt(Version20260101000000::class))->toBe('2026-01-01 00:00:00');
});

it('marks the migrations of the bundle as not executed when it uninstalls', function () {
    $installer = $this->migrationInstaller();
    $installer->install();

    $installer->uninstall();

    expect(executedTestMigrations())
        ->toBe([])
        ->and($installer->isInstalled())
        ->toBeFalse();
});

it('resets the migration filter after an install', function () {
    $installer = $this->migrationInstaller();

    $installer->install();

    $everyMigration = Container::get(FilteredMigrationsRepository::class)->getMigrations()->getItems();
    expect(availableMigrations())
        ->toHaveCount(count($everyMigration))
        ->toContain('OpenDxp\\Tests\\Application\\InstallerBundleExtension\\Migrations\\Version20260301000000');
});
