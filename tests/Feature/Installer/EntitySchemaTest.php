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

use OpenDxp\Db;

/**
 * @return list<string>
 */
function columnsOf(string $table): array
{
    $columns = Db::get()->createSchemaManager()->listTableColumns($table);

    return array_keys($columns);
}

function tableExists(string $table): bool
{
    return Db::get()->createSchemaManager()->tablesExist([$table]);
}

it('creates the tables of the bundle entities in every folder', function () {
    $installer = $this->schemaInstaller();

    $installer->install();

    expect(tableExists('installer_bundle_note'))
        ->toBeTrue()
        ->and(tableExists('installer_bundle_tag'))
        ->toBeTrue()
        ->and(tableExists('installer_bundle_note_tag'))
        ->toBeTrue();
});

it('skips the entities of a namespace that only starts like the bundle', function () {
    $installer = $this->schemaInstaller();

    $installer->install();

    expect(tableExists('installer_bundle_extension_other'))->toBeFalse();
});

it('brings an existing table of the bundle to its mapping', function () {
    Db::get()->executeStatement('CREATE TABLE installer_bundle_note (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id))');
    $installer = $this->schemaInstaller();

    $installer->install();

    expect(columnsOf('installer_bundle_note'))->toContain('title');
});

it('leaves every other table untouched', function () {
    Db::get()->executeStatement('CREATE TABLE installer_bundle_unrelated (id INT NOT NULL, PRIMARY KEY(id))');
    $installer = $this->schemaInstaller();

    $installer->install();

    expect(tableExists('installer_bundle_unrelated'))->toBeTrue();
});
