# Installers

Besides being enabled, bundles may need to execute installation tasks in order to be fully functional. This may concern
tasks like

* creating database tables
* creating or updating class definitions
* importing translations
* updating database tables or definitions after an update to a newer version
* ...

To give bundles full control over their install routines, OpenDXP only defines a basic installer interface which must be
implemented by your installer. The methods implemented by your installer is triggered from from commands like `opendxp:bundle:install`. The basic installer
interface can be found in [InstallerInterface](https://github.com/open-dxp/opendxp/blob/1.x/lib/Extension/Bundle/Installer/InstallerInterface.php) which
is implemented in [AbstractInstaller](https://github.com/open-dxp/opendxp/blob/1.x/lib/Extension/Bundle/Installer/AbstractInstaller.php)
which you can use as starting point.

A OpenDXP bundle is expected to return an installer instance in `getInstaller()`. This method can also return `null` if you
don't need any installation functionality. In this case, actions which would be handled by an installer will not be triggered 
by the command `opendxp:bundle:install`.

It's recommended to define the installer as service and to fetch it from the container from your bundle class on demand.  
As example:

```yml
services:
    App\Installer:
        public: true
```

```php
<?php

namespace App;

use OpenDxp\Extension\Bundle\AbstractOpenDxpBundle;

class App extends AbstractOpenDxpBundle
{
    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }
}
```

## Migrations

A common tasks in evolving bundles is to update an already existing/installed data structure to a newer version while also
supporting fresh installs of your bundle. To be able to apply versioned changes (migrations), OpenDXP integrates the
[Doctrine Migrations Bundle](https://symfony.com/doc/current/bundles/DoctrineMigrationsBundle/index.html)  which
provides a powerful migration framework.
For details how to work with migrations, please have a look at the [Doctrine Migrations Bundle documentation](https://symfony.com/doc/current/bundles/DoctrineMigrationsBundle/index.html).

### OpenDXP Specifics

OpenDXP added an additional option (`--prefix=`) to the migration commands of Doctrine, to be able to filter the migration versions
for a specific namespace. This gives you the possibility to control which migrations should be executed or not.
A typical use case for that would be to just run the OpenDXP core migrations or just the migrations for a specific bundle.

To make sure, the migration command only executes migrations from installed OpenDXP bundles, it is recommended to extend
the bundle migrations from `OpenDxp\Migrations\BundleAwareMigration` and implement the `getBundleName` method.
This abstract class checks if the given bundle is installed and skips the migration if necessary.  


#### Console Examples

```bash
# only run migrations for the OpenDXP core
./bin/console doctrine:migrations:migrate --prefix=OpenDxp\\Bundle\\CoreBundle

# list migrations for the CMF bundle
./bin/console doctrine:migrations:list --prefix=CustomerManagementFrameworkBundle\\Migrations

# run all migrations
./bin/console doctrine:migrations:migrate 
```  

#### Config Examples (`config.yaml`)
```yml
doctrine_migrations:
    migrations_paths:
        'OpenDxp\Bundle\DataHubBundle\Migrations': '@OpenDxpDataHubBundle/Migrations'
        'CustomerManagementFrameworkBundle\Migrations': '@OpenDxpCustomerManagementFrameworkBundle/Migrations'
```


## SettingsStore Installer

The `SettingsStoreAwareInstaller` adds the following functionality to the
default `AbstractInstaller`:

- Manage installation state with [Settings Store](../../../19_Development_Tools_and_Details/42_Settings_Store.md)
  (instead of checking executed migrations).
- Bring the tables of the Doctrine entities of the bundle to their mapping during install.
- Mark the migrations of the bundle as executed during install, without running them.
- Mark them as not executed again during uninstall.

### Implementation

Extend the `SettingsStoreAwareInstaller` and implement `install()` and `uninstall()`. At the end of both methods, call
the parent method, or call `$this->markInstalled()` and `$this->markUninstalled()`. This updates the Settings Store.

An installation builds the current state of the bundle from nothing: its tables, class definitions, permissions and
translations. Its migrations only bring an existing installation forward.

- `updateEntitySchema()` brings the tables of the Doctrine entities in the namespace of the bundle to their mapping,
  together with the join tables of their many-to-many associations. It touches no other table. A table that belongs to
  no entity is created by the installer itself, for example from an SQL file.
- `markMigrationsAsExecuted()` marks every migration in the namespace of the bundle as executed, so that none of them
  runs on the fresh installation later. A new migration needs no change in the installer.
- `markMigrationsAsNotExecuted()` resets them during uninstall.

The namespace of the bundle is the boundary for both methods, like `OpenDxp\Bundle\DummyBundle`. Entities and
migrations below it belong to the bundle. The entities use the default entity manager, as every bundle installation does.

```php 
<?php

namespace OpenDxp\Bundle\DummyBundle;

use OpenDxp\Extension\Bundle\Installer\SettingsStoreAwareInstaller;

class Installer extends SettingsStoreAwareInstaller
{
    public function install(): void
    {
        $this->updateEntitySchema();

        // build the rest of the current state of the bundle

        $this->markMigrationsAsExecuted();

        parent::install();
    }

    public function uninstall(): void
    {
        // remove what the bundle installed

        $this->markMigrationsAsNotExecuted();

        parent::uninstall();
    }
}
```

```yml 
    OpenDxp\Bundle\DummyBundle\Installer:
        public: true
        autowire: true
        arguments:
            $bundle: "@=service('kernel').getBundle('OpenDxpDummyBundle')"
```

`getLastMigrationVersionClassName()` is deprecated. Call `markMigrationsAsExecuted()` in `install()` instead.

### Installation
During installation of the bundle following things will happen:
- All statements of the `install` method are executed.
- If implemented correctly, the bundle is marked as installed in the SettingsStore.
- The tables of the entities of the bundle match their mapping.
- The migrations of the bundle are marked as executed (without actually executing them).

### Uninstallation
During uninstallation of the bundle following things will happen:
- All statements of the `uninstall` method are executed.
- If implemented correctly, the bundle is marked as uninstalled in the SettingsStore.
- The migrations of the bundle are marked as not executed (without actually executing them).

### Adding an installer to a released bundle
An installation that only ran the migrations has no installation state in the Settings Store. When a bundle gets its
first installer, it also ships a migration that marks such an installation as installed. This migration extends
`AbstractMigration`, not `BundleAwareMigration`, because `BundleAwareMigration` skips itself while the bundle is not
marked as installed.

```php
public function up(Schema $schema): void
{
    $this->addSql(
        'INSERT INTO settings_store (id, scope, type, data) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE data = VALUES(data);',
        ['BUNDLE_INSTALLED__' . OpenDxpDummyBundle::class, 'opendxp', 'bool', '1'],
    );
}
```

The installer refuses to run while the state of the bundle already exists, for example with `canBeInstalled()`
checking its tables.

### Migrations
Working with migrations is the same as described in the Migration section above.

---

For further details please see

* [Migrations](../../../19_Development_Tools_and_Details/37_Migrations.md)
* [Doctrine Migrations](https://www.doctrine-project.org/projects/migrations.html)
* [Doctrine Migrations Bundle](https://symfony.com/doc/master/bundles/DoctrineMigrationsBundle/index.html)
