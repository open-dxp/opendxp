# Migrating to OpenDXP

## Get started with OpenDXP 1.0

### Preparation
Upgrade to the latest Pimcore `11.5.x` first!

#### bin/console
- Replace the use-statements Pimcore with OpenDxp or download it from [skeleton](https://github.com/open-dxp/skeleton/blob/1.x/bin/console). After composer upgrade you'll able to run php bin/console again and debug any missing replacements.
#### Settings Store
- In table `settings_store`, replace `BUNDLE_INSTALLED__Pimcore` with `BUNDLE_INSTALLED__OpenDxp`
#### Methods
- getPimcoreUser => getOpenDxpUser
#### Configuration
- Autoload directory: config/pimcore => config/opendxp
- All Pimcore configuration blocks `pimcore_*` (like pimcore_admin) => `opendxp_*`. This affects config and var/config folder.
#### Constants
- All Constants `PIMCORE_` => `OPENDXP_`
#### Messenger
- All runners from `pimcore_` => `opendxp_`
#### Commands
- All commands from `pimcore:` => `opendxp:`
#### Twig
- All twig methods changed from `pimcore_` => `opendxp_`

If you want to migrate your existing document, asset and object versions please read this: [Version Migration](Version_Migration.md)

Note that you might have hardcoded pimcore class references in `documents_editables` or `properties` table.
Check:
```sql
SELECT documentId, name, type, SUBSTRING(data, 1, 200)
  FROM documents_editables
  WHERE data LIKE '%Pimcore%'
  LIMIT 20;
 SELECT * FROM properties WHERE data LIKE '%Pimcore%' LIMIT 20;
```
It is safe to replace the occurrences, even in serialized data. Adjust the SQL to your needs:
```sql
UPDATE documents_editables
  SET data = REPLACE(data, 'Pimcore\\', 'OpenDxp\\')
  WHERE data LIKE '%Pimcore%';
UPDATE properties
  SET data = REPLACE(data, 'Pimcore\\', 'OpenDxp\\')
  WHERE data LIKE '%Pimcore%';
```

***

### Breaking Changes

#### Core
- ⚠️ **Important:** Removed hardcoded password salt in `OpenDxp\Tool\Authentication::preparePlainTextPassword()`.
  > As a BC layer the new config option `opendxp.security.password.salt` was introduced. Set it to "pimcore" to keep 
    password logins of existing installations working.
- Renamed `OpenDxp\Routing\Loader\AnnotatedRouteControllerLoader` to `OpenDxp\Routing\Loader\AttributeRouteControllerLoader`.
- `OpenDxp\Extension\Document\Areabrick\AbstractAreabrick` no longer implements `Symfony\Component\DependencyInjection\ContainerAwareInterface`. Use dependency injection for services instead.
- Removed `symfony/templating` as core dependency; fully rely on Twig now.
  - Replace any usage of `OpenDxp\Templating\TwigDefaultDelegatigEngine` with Twig (via DI).
  - `OpenDxp\Navigation\Renderer\AbstractRenderer::$templatingEngine` is now of type `Twig\Environment`.
  - `OpenDxp\Document\Editable\EditableHandler::$templating` is now of type `Twig\Environment`.
  - Removed `OpenDxp\Controller\Controller`. Use `Symfony\Bundle\FrameworkBundle\Controller\AbstractController` instead.
- `OpenDxp\Model\Element\ElementInterface::getById()` changed signature. Check your code for implementations.
- `OpenDxp\Workflow\Manager::getWorkflowByName()` changed return type to `null|Symfony\Component\Workflow\WorkflowInterface`.

##### ⚠️ Migrations
- Removed all migrations from `Pimcore\Bundle\CoreBundle\Migrations` namespace.
  - Delete corresponding entries from `migration_versions` table in your database.

### Wysiwyg
The suggested Quill editor lacks the capabilities expected in a professional CMS.
As a result, we’ve reintroduced the TinyMCE bundle. (TinyMCE has changed its open-source license to GPL, which is acceptable for our use.)

### Misc
- Core: Removed PlatformVersion
- Skeleton: Removed QuillBundle

### Removed Deprecations
- `OpenDxp\Model\Element\Service::getElementById()`: Parameter `$id` no longer supports `string`; use `int` instead.
- `OpenDxp\Model\Document::getById()`: Parameter `$id` no longer supports `string`; use `int` instead.
- Removed deprecated method `OpenDxp\Model\DataObject\Service::getHelperDefinitions()`. Use `OpenDxp\Bundle\AdminBundle\Service\GridData\DataObject::getHelperDefinitions()` instead (requires `open-dxp/admin-bundle`).
- Removed deprecated method `OpenDxp\Model\DataObject\Service::gridObjectData()`. Use `OpenDxp\Bundle\AdminBundle\Service\GridData\DataObject::getData()` instead (requires `open-dxp/admin-bundle`).
- Removed deprecated method `OpenDxp\Model\DataObject\Service::getInheritedData()`. Use `OpenDxp\Bundle\AdminBundle\Service\GridData::getInheritedData()` instead (requires `open-dxp/admin-bundle`).
- Removed BC layer `OpenDxp\Model\DataObject\Service::getVersionDependentColumnName()` (conversion of `o_`-prefixed columns/properties). Migrate or delete versions before upgrading.
- Removed `OpenDxp\Model\DataObject\ClassDefinition\DynamicOptionsProvider\MultiSelectOptionsProviderInterface`. Use `...\SelectOptionsProviderInterface` instead.
- Removed support for any password algorithm other than `password_hash` in `OpenDxp\Model\DataObject\ClassDefinition\Data\Password`.
- `OpenDxp\Model\DataObject\ClassDefinition\Data`:
  - Removed support to pass `null` values to `setIndex()`.
  - Removed deprecated method `getAsIntegerCast()`.
  - Removed deprecated method `getAsFloatCast()`.
  - Removed deprecated property `$fieldtype`.
- `OpenDxp\Model\DataObject\AbstractObject::getById()`: Parameter `$id` no longer supports `string`; use `int` instead.
- `OpenDxp\Model\Asset::getById()`: Parameter `$id` no longer supports `string`; use `int` instead.
- Removed deprecated method `OpenDxp\Model\Asset\Image::getThumbnailConfig()`. Use `OpenDxp\Model\Asset\Image::getThumbnail()->getConfig()` instead.
- `OpenDxp\Tool\Admin` file-based maintenance mode removed:
  - Removed `getMaintenanceModeFile()` and `getMaintenanceModeScheduleLoginFile()`.
  - Removed `activateMaintenanceMode()`/`deactivateMaintenanceMode()`. Use `OpenDxp\Tool\MaintenanceModeHelper::activate()`/`::deactivate()` instead.
  - Removed `isInMaintenanceMode()`. Use `OpenDxp\Tool\MaintenanceModeHelper::isActive()` instead.
  - Removed `isMaintenanceModeScheduledForLogin()` / `scheduleMaintenanceModeOnLogin()` / `unscheduleMaintenanceModeOnLogin()`.
- `OpenDxp\Event\Model\Asset\ResolveUploadTargetEvent`: removed deprecated `getContext()`, `setContext()` and `$context`. Use `setArgument()` instead.
- `OpenDxp\Bundle\XliffBundle\ExportDataExtractorService\DataExtractor\DocumentDataExtractor`: removed deprecated `addDoumentEditables()`; use `addDocumentEditables()` instead.
- Removed deprecated class `OpenDxp\Helper\CsvFormulaFormatter`; use `League\Csv\EscapeFormula` instead.
- `OpenDxp\Image\Adapter\Imagick`: removed deprecated option to use relative path for image thumbnail in methods `addOverlay()` and `addOverlayFit()`.
- Removed native Chromium support. Use gotenberg instead.
  - Removed `OpenDxp\Image\HtmlToImage::convertChromium()` and `::getChromiumBinary()`.
  - Removed class `OpenDxp\Image\Chromium`.
  - Removed config node `opendxp.chromium`.
- Removed deprecated methods in `OpenDxp\Bundle\ApplicationLoggerBundle\ApplicationLogger`: `setRelatedObject()`, `setFileObject()`.
- Removed deprecated config node `opendxp.general.language`.
- `OpenDxp\Image\Adapter`: removed deprecated abstract methods now provided by `OpenDxp\Image\AdapterInterface`: `load()`, `save()`, `getContentOptimizedFormat()`, `supportsFormat()`.
- Removed deprecated static property `OpenDxp\Extension\Bundle\AbstractOpenDxpBundle::$bundleManager`.
- Removed deprecated method `OpenDxp\Tool\Console::runPhpScriptInBackground()`.
- Removed deprecated method `OpenDxp\Tool::getCachedSymfonyEnvironments()`.
- Removed deprecated static property `OpenDxp\Navigation\Page::$_defaultPageType`.
- Removed deprecated method `OpenDxp\Model\Element\Recyclebin\Item::getStoreageFile()`.
- Removed deprecated GET method for `OpenDxp\Bundle\ApplicationLoggerBundle\Controller\LogController::showAction()`. Use POST instead.
- Removed deprecated console option `--generator` for `opendxp:image:low-quality-preview` (`OpenDxp\Bundle\CoreBundle\Command\LowQualityImagePreviewCommand`).

#### PHP & Symfony deprecations
- Removed dotenv variable BC layer in `OpenDxp\Bootstrap::bootstrap()`. Use Symfony Runtime (public/index.php & bin/console.php).
- Twig templating deprecations (use Twig instead of Symfony Templating):
  - `OpenDxp\Twig\Extension\Templating\Placeholder\Container`: removed `captureStart()` and `captureEnd()`; use Twig set tag for output capturing.
  - `OpenDxp\Twig\Extension\Templating\HeadStyle`: removed `captureStart()` and `captureEnd()`; use Twig set tag for output capturing.
  - `OpenDxp\Twig\Extension\Templating\HeadScript`: removed `captureStart()` and `captureEnd()`; use Twig set tag for output capturing.
  - Removed deprecated `opendxp_cache` Twig function (`OpenDxp\Twig\Extension\CacheExtension`); use the `opendxpcache` tag instead.
  - Removed enable_authenticator_manager from security.yaml due to symfony 7.3

### Templates
- Removed `key_value_table.html.twig` from `CoreBundle`
