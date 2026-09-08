# Upgrade Notes

## OpenDXP 1.4.1
- Bugfix: Fix NULL writes into NOT NULL columns in classificationstore configs [#179](https://github.com/open-dxp/opendxp/pull/179)
- Bugfix: Fix application log archive growing with duplicate rows [#185](https://github.com/open-dxp/opendxp/pull/185)
- Bugfix: Only resolve pretty URLs for documents that are still pages [#186](https://github.com/open-dxp/opendxp/pull/186)
- Bugfix: Fix pretty URL resolving to a document from another site [#187](https://github.com/open-dxp/opendxp/pull/187)
- Bugfix: Ignore pretty URLs when checking whether a document path exists [#188](https://github.com/open-dxp/opendxp/pull/188)

## OpenDXP 1.4.0
- New Feature: Tag-based http cache invalidation [#153](https://github.com/open-dxp/opendxp/pull/153)
- New Feature: Introduce Permission Voters (User / Element) [#171](https://github.com/open-dxp/opendxp/pull/171)
- Improvement: Add indexed relation lookup as opt-in fast path, keep LIKE fallback unchanged [#169](https://github.com/open-dxp/opendxp/pull/169)
- Improvement: Performance: reduce queries and allocations in element loading and caching [@Cruiser13](https://github.com/open-dxp/opendxp/pull/162)
- Bugfix: Make thumbnail generation errors in PublicServicesController more actionable by logging additional information [@NiklasBr](https://github.com/open-dxp/opendxp/pull/161)
- Bugfix: Nack failed asset preview thumbnail generation instead of silent ack [@Cruiser13](https://github.com/open-dxp/opendxp/pull/163)
- Bugfix: Recycle Bin: Avoid rmdir on storage root [#167](https://github.com/open-dxp/opendxp/pull/167)
- Bugfix: `ImageThumbnailInterface::getAsset()` and `ImageThumbnailTrait::getAsset()` now declare a nullable return type (`?Asset` instead of `Asset`), matching the already-nullable `$asset` property. A thumbnail built without a backing asset (e.g. from a raw path reference) previously threw a `TypeError` because `getAsset()` returned `null` while promising a non-nullable `Asset`. Note: this widens the return type of a public API method to nullable — callers must now handle a possible `null` return value [@blankse](https://github.com/open-dxp/opendxp/pull/166)
- Bugfix: Make invisible fields in field-collections editable [@Simon-Drohsen](https://github.com/open-dxp/opendxp/pull/175)
- Bugfix: Restore ReverseObjectRelation `ownerClassId` on definition import [@alexloetscher95](https://github.com/open-dxp/opendxp/pull/177)
- Chore: Deprecate NotificationServiceFilterParser class [#164](https://github.com/open-dxp/opendxp/pull/164)

## OpenDXP 1.3.3
- Chore: Fix infinite-loop typo and stale routing docblock [#156](https://github.com/open-dxp/opendxp/pull/156)
- Chore: Refactor composite index generation, permission checks, and serialization handling [#155](https://github.com/open-dxp/opendxp/pull/155):
  - Optimized composite index handling with static callbacks and safer query parameterization
  - Improved WebDAV asset permission checks for rename, move, and overwrite actions
  - Standardized serialization/unserialization with Serialize utility throughout the codebase
  - Introduced InvalidQueryException for safer query validation in custom reports
  - Added isAllowedForUser validation for report configuration access
  - Enhanced CustomReportController with reusable loadReport and isValidConfigName methods, reducing duplicate logic

## OpenDXP 1.3.2
- Improvement: `UnmanagedTablesSchemaFilter` (formally `IgnoreCoreTablesFilterListener`) is now disabled by default and only activates during `doctrine:schema:update`, `doctrine:schema:validate`, and commands implementing `ExcludesUnmanagedTablesInterface`
  - New Feature: If `ExcludesUnmanagedTablesInterface` is implemented on any console command that calls `SchemaTool::updateSchema()` directly to prevent unintended DROP TABLE statements for non-ORM tables, see [docs](../../19_Development_Tools_and_Details/06_Doctrine_Schema_Filter.md)
- Improvement: Add `forceResize` parameter to video scaling methods
- Improvement: Update `Ffmpeg` processing for improved format handling and codec support
- Improvement: Migrate Twig extensions to attributes
- Bugfix: return exact language match before null-language fallback in `Asset::getMetadata()`
- Improvement: Collect Dateformats and centalize them in a single location with documentation

## OpenDXP 1.3.1
- Bugfix: Centralize brickPrefix handling in RelationFilterConditionParser [#120](https://github.com/open-dxp/opendxp/pull/120)
- Bugfix: fix translation caching bugs and deprecate unused listing methods [#123](https://github.com/open-dxp/opendxp/pull/123)
- Improvement: PHP 8.5 support added
- Improvement: Remove more deprecated code usages [#112](https://github.com/open-dxp/opendxp/pull/112), [#113](https://github.com/open-dxp/opendxp/pull/113)
- Improvement: Refactor findForbiddenPaths to improve performance, combining user and role queries into a single optimized query [#115](https://github.com/open-dxp/opendxp/pull/115)
- Improvement: Compatibility fix with Symfony UX TwigComponent/LiveComponent
- Improvement: Improve path and key validation with explicit encoding and tests [#128](https://github.com/open-dxp/opendxp/pull/128)
- Improvement: Use Request Context for improved URL handling [#127](https://github.com/open-dxp/opendxp/pull/127)
- Improvement: Remove POEditor support and normalize translation files [#129](https://github.com/open-dxp/opendxp/pull/129)
- Improvement: Fix cross-site document path resolution in hardlink context [#132](https://github.com/open-dxp/opendxp/pull/132)

## OpenDXP 1.3.0
- Bugfix: Fix Video Processor when TmpStore is missing
- Improvement: Use try-finally for disable/enable versioning
- Improvement: Refactor SQL queries: enforce parameterized bindings and consistent style
- Improvement: Refactor constants
- New Feature: add site custom settings
- New Feature: Introduce `GeneralHostResolver` service for centralized domain resolution, see [docs](../../02_MVC/04_Routing_and_URLs/06_Domain_and_Host_Handling.md)

## OpenDXP 1.2.2
- Bugfix: Update upgrade notes and handle migration cleanup in CoreBundle
- Bugfix: Update IgnoreCoreTablesFilterListener

## OpenDXP 1.2.1
- Improvement: DataObject\Listing: Add missing PhpDocs
- Bugfix: Snippets will be cached with wrong links
- Bugfix: Make translations sortable by dates
- Improvement: `Translator::checkForEmptyTranslation()` - getValidLanguages needs to accept domain parameter
- Improvement: Use preferred tile.openstreetmap.org URL
- Improvement: Add IgnoreCoreTablesFilterListener
- Improvement: Improve Generic Execution Engine Entities & Manager 
- Improvement: [ApplicationLogger] standardize quotes and improve code readability in js files 
- Improvement: update FOSJsRoutingBundle routing resource to use PHP configuration
- Improvement: Replace deprecated Doctrine DBAL query introspection methods
- Improvement: Update version references

## OpenDXP 1.2.0

### [Core]
- Improvement: Symfony 7.4 compatibility (bumped minimum required version to `^7.4`)
- Improvement: Code cleanup, performance improvements & modernized code style.
- Deprecated `lib/helper-functions.php`. Use static helpers from `OpenDxp\Helper\*` instead.

### [AdminBundle]
- Improvement: Various UI refinements like colors, icons and some general styling
- Improvement: Symfony 7.4 compatibility
- Improvement: Code cleanup, performance improvements & modernized code style.
- Feature: Added poster image, title and description for every video type (video overlay)

## OpenDXP 1.1.3

### [Core]
- BlockElement: Add SetNullFilter for '_owner' property in DeepCopy configuration to improve performance.

## OpenDXP 1.1.0

### [Core]
- Added `open-dxp/admin-bundle` as core dependency since this is currently the only backend UI available.

### [AdminBundle]
- Renamed `open-dxp/admin-ui-classic-bundle` to `open-dxp/admin-bundle`.
  - PHP namespace has **not** changed.
- Abandoned composer package `open-dxp/admin-ui-classic-bundle`.

### [SeoBundle]
- Performance improvements for redirect resolving in `RoutingListener`.

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
