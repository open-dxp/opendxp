<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Installer;

use OpenDxp\Db;
use OpenDxp\Migrations\FilteredMigrationsRepository;
use OpenDxp\Tests\Application\InstallerBundle\Migrations\Version20260101000000;
use OpenDxp\Tests\Application\InstallerBundle\Migrations\Version20260201000000;
use OpenDxp\TestFoundation\Container;

afterEach(fn () => forgetTestInstallation());

it('marks every migration of the bundle as executed when it installs', function () {
    $installer = migrationInstaller();
    $installer->install();

    expect(executedTestMigrations())
        ->toBe([
            Version20260101000000::class,
            Version20260201000000::class,
        ])
        ->and($installer->isInstalled())
        ->toBeTrue();
});

it('leaves the migrations of a namespace that only starts like the bundle alone', function () {
    migrationInstaller()->install();

    expect(executedTestMigrations())
        ->not->toContain('OpenDxp\\Tests\\Application\\InstallerBundleExtension\\Migrations\\Version20260301000000');
});

it('keeps a migration that already ran', function () {
    Db::get()->insert('migration_versions', [
        'version' => Version20260101000000::class,
        'executed_at' => '2026-01-01 00:00:00',
    ]);

    migrationInstaller()->install();

    expect(Db::get()->fetchOne('SELECT executed_at FROM migration_versions WHERE version = ?', [Version20260101000000::class]))
        ->toBe('2026-01-01 00:00:00');
});

it('marks the migrations of the bundle as not executed when it uninstalls', function () {
    $installer = migrationInstaller();
    $installer->install();
    $installer->uninstall();

    expect(executedTestMigrations())
        ->toBe([])
        ->and($installer->isInstalled())
        ->toBeFalse();
});

it('leaves no prefix behind for the next user of the migration repository', function () {
    migrationInstaller()->install();

    expect(availableMigrations())
        ->toHaveCount(count(Container::get(FilteredMigrationsRepository::class)->getMigrations()->getItems()))
        ->toContain('OpenDxp\\Tests\\Application\\InstallerBundleExtension\\Migrations\\Version20260301000000');
});
