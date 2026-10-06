<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Installer;

use OpenDxp\Db;

afterEach(fn () => forgetTestInstallation());

it('creates the tables of the entities in the namespace of the bundle, in whichever folder they live', function () {
    schemaInstaller()->install();

    expect(tableExists('installer_bundle_note'))
        ->toBeTrue()
        ->and(tableExists('installer_bundle_tag'))
        ->toBeTrue()
        ->and(tableExists('installer_bundle_note_tag'))
        ->toBeTrue();
});

it('leaves the entities of a namespace that only starts like the bundle alone', function () {
    schemaInstaller()->install();

    expect(tableExists('installer_bundle_extension_other'))
        ->toBeFalse();
});

it('brings an existing table of the bundle to its mapping', function () {
    Db::get()->executeStatement('CREATE TABLE installer_bundle_note (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id))');

    schemaInstaller()->install();

    expect(columnsOf('installer_bundle_note'))
        ->toContain('title');
});

it('leaves every other table untouched', function () {
    Db::get()->executeStatement('CREATE TABLE installer_bundle_unrelated (id INT NOT NULL, PRIMARY KEY(id))');

    schemaInstaller()->install();

    expect(tableExists('installer_bundle_unrelated'))
        ->toBeTrue();
});
