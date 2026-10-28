# Advanced Installation Topics

To fully automate the installation process, options can be passed in the CLI as parameters, rather than adding them interactively. 

##### For Docker installation:

```bash
docker compose exec php vendor/bin/opendxp-install --admin-username=admin --admin-password=admin \
  --mysql-username=username --mysql-password=password --mysql-database=opendxp \
  --mysql-host-socket=127.0.0.1 --mysql-port=3306 \
  --no-interaction
```

##### For webserver installation:

```bash
./vendor/bin/opendxp-install --admin-username=admin --admin-password=admin \
  --mysql-username=username --mysql-password=password --mysql-database=opendxp \
  --mysql-host-socket=127.0.0.1 --mysql-port=3306 \
  --no-interaction
```

> [!NOTE]
> The `--no-interaction` flag will prevent any interactive prompts.

To avoid having to pass sensitive data (e.g. DB password) as command line option, you can also set each parameter as env
variable. See `./vendor/bin/opendxp-install` for details. Example:

```bash
$ OPENDXP_INSTALL_MYSQL_USERNAME=username OPENDXP_INSTALL_MYSQL_PASSWORD=password ./vendor/bin/opendxp-install \
  --admin-username=admin --admin-password=admin \
  --mysql-database=opendxp \
  --no-interaction
```

### Installing Bundles

#### Core and Extension Bundles

The installer offers two kinds of bundles:

- **Core bundles** ship with OpenDXP, see [Core Bundles](#core-bundles).
- **Extension bundles** are all installed composer packages of type `opendxp-bundle` that are no core bundles. The
  installer finds them on its own, so a bundle you add with `composer require` before the installation is offered as well.

In interactive mode, the installer asks for each kind separately. It preselects the bundles that `config/bundles.php`
already registers and the bundles a [subscriber recommends](#modifying-required-bundles-and-bundle-recommendations).
Without either, no bundle is preselected. When bundles are preselected, the question whether to install that kind
defaults to yes.

#### Overview of Bundle Lists

When installing, you will [interact with](#modifying-required-bundles-and-bundle-recommendations) two lists of
bundles: **Installable Bundles** and **Required Bundles**.

- **Installable Bundles**:
    - Displayed to users during interactive mode. Recommended ones are preselected.
    - These are the bundles users can specify when using the `--install-bundles=commaSeparatedBundleList` option.

- **Required Bundles**:
    - These bundles will automatically be installed in interactive mode, if the user choses to install bundles.
    - They are installed whenever the `--install-bundles` option is set.

#### Core Bundles

The installer offers these core bundles. None of them is recommended by default:

- OpenDxpAdminBundle (the Admin UI)
- [OpenDxpApplicationLoggerBundle](../../18_Tools_and_Features/17_Application_Logger.md)
- [OpenDxpCustomReportsBundle](../../18_Tools_and_Features/29_Custom_Reports.md)
- [OpenDxpGlossaryBundle](../../18_Tools_and_Features/21_Glossary.md)
- OpenDxpSeoBundle (for SEO-related topics: [Site Declarations](../../18_Tools_and_Features/38_Site_Declarations.md), [Sitemaps](../../18_Tools_and_Features/39_Sitemaps.md) and [Redirects](../../02_MVC/04_Routing_and_URLs/04_Redirects.md))
- OpenDxpSimpleBackendSearchBundle (for default search functionality in Backend UI interface)
- [OpenDxpStaticRoutesBundle](../../02_MVC/04_Routing_and_URLs/02_Custom_Routes.md)
- OpenDxpTinymceBundle (the WYSIWYG editor)
- [OpenDxpUuidBundle](../../19_Development_Tools_and_Details/19_UUID_Support.md)
- OpenDxpWordExportBundle (for import/export functionality for translations in Word format)
- OpenDxpXliffBundle (for import/export functionality for translations in Xliff format)
- OpenDxpGenericExecutionEngineBundle

#### Automating Bundle Installation

Three options select the bundles. Combine them as you like. The installer always adds the required bundles.

In interactive mode, a `--with-*` flag skips only the question for its kind, so the installer still asks for the
other kind. `--install-bundles` skips both questions. With `--no-interaction`, the installer asks no question at all.

| Option                     | Env var                                  | Installs                                     |
|----------------------------|------------------------------------------|----------------------------------------------|
| `--with-core-bundles`      | `OPENDXP_INSTALL_WITH_CORE_BUNDLES`      | all core bundles                             |
| `--with-extension-bundles` | `OPENDXP_INSTALL_WITH_EXTENSION_BUNDLES` | all extension bundles                        |
| `--install-bundles[=list]` | `OPENDXP_INSTALL_INSTALL_BUNDLES`        | the bundles of the comma separated list      |

The installer warns about a bundle in `--install-bundles` that it does not know and skips it.

**Note**: The bundles will be automatically added to `config/bundles.php`.

```bash
./vendor/bin/opendxp-install --admin-username=admin --admin-password=admin \
--mysql-username=username --mysql-password=password --mysql-database=opendxp \
--mysql-host-socket=127.0.0.1 --mysql-port=3306 \
--with-core-bundles \
--install-bundles=OpenDxpNewsBundle \
--no-interaction
```

#### Modifying Required Bundles and Bundle Recommendations
The `BundleSetupEvent` is triggered under two circumstances:

1. To preset the installable and the automatically installed (required) bundles for the `--install-bundles` option.
2. To recommend bundles, which interactive mode preselects. Required bundles are not installed if the user declines to install bundles.

By subscribing or listening to the `BundleSetupEvent`, you can add or remove installable, recommended or required bundles.

For practical examples, refer to the [OpenDXP Skeleton](https://github.com/open-dxp/skeleton). It shows how the [Admin UI Classic Bundle](https://github.com/open-dxp/admin-bundle) is integrated.

```php
<?php

namespace App\EventSubscriber;

use OpenDxp\Bundle\AdminBundle\OpenDxpAdminBundle;
use OpenDxp\Bundle\InstallBundle\Event\BundleSetupEvent;
use OpenDxp\Bundle\InstallBundle\Event\InstallEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BundleSetupSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            InstallEvents::EVENT_BUNDLE_SETUP => [
                ['bundleSetup'],
            ],
        ];
    }

    public function bundleSetup(BundleSetupEvent $event): void
    {
        // make bundle installable (using --install-bundles) and recommend it in interactive installation
        $event->addInstallableBundle('OpenDxpAdminBundle', OpenDxpAdminBundle::class, true);

        // add required bundle
        $event->addRequiredBundle('OpenDxpAdminBundle', OpenDxpAdminBundle::class);
    }
}
```

`$event->getExtensionBundles()` returns the extension bundles the installer found. To hide one of them, remove it
with `$event->removeInstallableBundle('OpenDxpNewsBundle')`. `$event->clearBundlesAndRecommendations()` clears the
core and the extension bundles, so the installer asks no bundle question at all.

Make sure to register your listener/subscriber under `config/installer.yaml` as described in [Preconfiguring the Installer](#preconfiguring-the-installer).

```yaml
services:
    # default configuration for services in *this* file
    _defaults:
        # automatically injects dependencies in your services
        autowire: true
        # automatically registers your services as commands, event subscribers, etc.
        autoconfigure: true
        # this means you cannot fetch services directly from the container via $container->get()
        # if you need to do this, you can override this setting on individual services
        public: false

    # ---------------------------------------------------------
    # Event Subscribers
    # ---------------------------------------------------------
    App\EventSubscriber\BundleSetupSubscriber: ~

```

### Preconfiguring the Installer

You can preconfigure the values used by the installer by adding a config file which sets values for the database
credentials. This is especially useful when installing OpenDXP on platforms where credentials are available via env vars
instead of having direct access to them. To preconfigure the installer, add a config file in `config/installer.yaml` 
(note: the file can be of any format supported by Symfony's config, so you could also use xml or php as the format), then configure the `opendxp_install` tree:

```yaml
# config/installer.yaml

opendxp_install:
    parameters:
        database_credentials:
            user:                 username
            password:             password
            dbname:               opendxp
            
            # env variables can be directly read with the %env() syntax
            # see https://symfony.com/blog/new-in-symfony-3-2-runtime-environment-variables
            host:                 "%env(DB_HOST)%"
            port:                 "%env(DB_PORT)%"
```

## Set a Time Zone
Make sure to set the corresponding time zone in your configuration. 
It will be used for displaying date/time values in the admin backend.

```yaml
opendxp:
    general:
        timezone: Europe/Berlin
```

## Office Document Preview

The feature for displaying a [preview of documents](../../04_Assets/03_Working_with_Thumbnails/05_Document_Thumbnails.md) directly in OpenDXP is optional. To use it, you must install either [Gotenberg](../../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md#gotenberg) or [LibreOffice](../../23_Installation_and_Upgrade/03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md#libreoffice-pdftotext-inkscape-) according to your preference.
