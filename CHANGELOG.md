# Changelog

## 1.5.0
- [SECURITY] The simple backend search quotes the workspace paths in its permission condition and escapes LIKE wildcards in them. The allowed paths below a forbidden folder went into the SQL unquoted [#261](https://github.com/open-dxp/opendxp/pull/261)
- [NEW FEATURE] `GeneralHostProviderInterface::CONTEXT_SOURCE` names the `source` key of the general host context, which holds the request. See [Domain and Host Handling](doc/02_MVC/04_Routing_and_URLs/06_Domain_and_Host_Handling.md)
- [NEW FEATURE] `TrustedHosts` knows the general domain, the site domains and `opendxp.security.trusted_hosts`. `opendxp.security.enforce_trusted_hosts: true` answers every request to another host with 400. See [Domain and Host Handling](doc/02_MVC/04_Routing_and_URLs/06_Domain_and_Host_Handling.md#trusted-hosts) [#234](https://github.com/open-dxp/opendxp/pull/234)
- [NEW FEATURE] [HIGHLIGHT] [OPT-IN] Site declarations serve robots.txt, security.txt, ads.txt, app-ads.txt and llms.txt per site. Only robots.txt is enabled after the update. See [Site Declarations](doc/18_Tools_and_Features/38_Site_Declarations.md) [#84](https://github.com/open-dxp/opendxp/issues/84)
  > Serve robots.txt, security.txt, ads.txt, app-ads.txt and llms.txt for each site, straight from OpenDXP. Only robots.txt is enabled by default. The others are opt-in.
  1. Enable a site declaration under `opendxp_seo.site_declarations`, for example `security_txt: true`.
- [CHORE] robots.txt needs the SeoBundle. A project without it answers `/robots.txt` with 404.
- [CHORE] Remove the internal `PublicServicesController::robotsTxtAction()`, `OpenDxp\Bundle\SeoBundle\Config` and the admin routes `opendxp_bundle_seo_settings_robotstxtget` and `opendxp_bundle_seo_settings_robotstxtput`.
- [CHORE] [FOLLOW-UP] The full page cache keeps old entries of `/robots.txt` after the update
  1. Run `bin/console opendxp:cache:clear --tags=output,output_lifetime`.
- [DEPRECATION] The permission `robots.txt`. It stays until 2.0 because of BC reasons. Use `site_declarations` instead. A migration grants it to every user and role with `robots.txt`.
- [NEW FEATURE] The redirects, the custom reports and the application log use the unified grid export of the admin bundle.
- [CHORE] Remove the old export routes of the redirects and the custom reports.
- [CHORE] Remove `OpenDxp\Bundle\SeoBundle\Redirect\Csv::createExportWriter()`.
- [CHORE] Require `open-dxp/admin-bundle` ^1.5
- [NEW FEATURE] The test API adds `CustomReportFactory`.
- [NEW FEATURE] The test API adds `EmailLogFactory` and `AssetImageFactory::withMetadata()`.
- [CHORE] Remove `chrome-php/chrome` and `behat/gherkin` from `require-dev`.
- [CHORE] Replace Codeception with Pest and `open-dxp/test-foundation`. See [Testing](doc/19_Development_Tools_and_Details/29_Testing/README.md).
- [DEPRECATION] Every class under `OpenDxp\Tests\Support`. They stay until 2.0 because of BC reasons. Use `open-dxp/test-foundation` and the factories in `OpenDxp\Test\Factory` instead.
- [DEPRECATION] `OpenDxp\Test\KernelTestCase` and `OpenDxp\Test\WebTestCase`. They stay until 2.0 because of BC reasons. Use `OpenDxp\TestFoundation\TestCase` instead of `KernelTestCase` and `OpenDxp\TestFoundation\BrowserTestCase` instead of `WebTestCase`.
- [ENHANCEMENT] Allow `symfony/process` 8.
- [BUGFIX] Moving a page with a pretty URL no longer creates a redirect for its former path in the document tree. Visitors reach the page under its pretty URL, which stays the same [#194](https://github.com/open-dxp/opendxp/issues/194)
- [ENHANCEMENT] Redirects no longer cost a database query on every request. A URL looks up its redirect only when no document or route answers it, or when a redirect with priority 99 exists [#151](https://github.com/open-dxp/opendxp/issues/151)
- [NEW FEATURE] Count how often each redirect answers a request, and when it did last. Switch it off with `opendxp_seo.redirects.count_hits: false` [#151](https://github.com/open-dxp/opendxp/issues/151)
- [NEW FEATURE] Domain redirects send every request to a host somewhere else, optionally with the path of the request. They run before the redirect to the main domain [#151](https://github.com/open-dxp/opendxp/issues/151)
- [NEW FEATURE] Redirects can be valid from a date, in addition to the expiry [#151](https://github.com/open-dxp/opendxp/issues/151)
- [NEW FEATURE] Protected redirects are visible only to users with the new permission `redirects_protected`. They win over every redirect that is not protected, and nobody without the permission can take over their source [#151](https://github.com/open-dxp/opendxp/issues/151)
- [ENHANCEMENT] The redirect editor refuses invalid regular expressions, redirects to their own source, domain redirects that would stay on their domain and an expiry before the start. It warns about duplicate sources and chains. It saves only the fields an editor may set, and a field left empty keeps its current value. The CSV import applies the same checks and skips the rows that fail them [#151](https://github.com/open-dxp/opendxp/issues/151)
- [ENHANCEMENT] `410 Gone` is one of the default redirect status codes [#151](https://github.com/open-dxp/opendxp/issues/151)
- [NEW FEATURE] [HIGHLIGHT] The redirect editor shows the hits of each redirect, edits all fields of a redirect in a window, narrows the grid to a view like "no hit for 90 days" and changes the redirects selected by their checkbox at once [#151](https://github.com/open-dxp/opendxp/issues/151)
  > See how often each redirect is hit and filter the grid by views such as "no hits in 90 days". Edit a redirect in a dialog, or update all selected redirects at once.
- [NEW FEATURE] Create a redirect from a URL in the HTTP error log [#151](https://github.com/open-dxp/opendxp/issues/151)
- [ENHANCEMENT] The CSV export of redirects adds the columns `validFrom`, `passThroughPath` and `protected`. Older files still import [#151](https://github.com/open-dxp/opendxp/issues/151)
- [NEW FEATURE] [HIGHLIGHT] `OpenDxp\Test` holds the test API for projects and bundles. `OpenDxp\Test\Factory` has Foundry factories for the models of OpenDXP, with base classes for factories of your own. `OpenDxp\Test\Expectation\Fields` registers the Pest expectations `toCarryField()` and `toCarryLocalizedField()`. `OpenDxp\Test\Expectation\Redirects` registers `toRedirectTo()`, `toBeAnsweredBy()` and `toBeAnsweredWithoutRedirect()`. `ClassDefinitions`, `Fieldcollections`, `ObjectBricks` and `ClassificationStores` install definitions from JSON files. `Settings::override()` changes the system configuration for one test. `OpenDxp\Test\Document\Brick` describes one instance of an areabrick, and the page, snippet and email factories place bricks with `withBricks()`. `OpenDxp\Test\Document\Areablocks::filledWith()` fills an areablock inside a brick. See [Testing](doc/19_Development_Tools_and_Details/29_Testing/README.md).
  > Test your projects and bundles with the OpenDXP test API: Foundry factories for every model, Pest expectations for fields and redirects, and areabricks filled in a single call.
- [ENHANCEMENT] The HTTP error log and the redirect hits are written after the response has been sent. Repeated errors of one URI are counted in a single row, also under concurrent requests. A URL that a redirect answers is no longer logged [#151](https://github.com/open-dxp/opendxp/issues/151)
- [NEW FEATURE] [HIGHLIGHT] A URL slug field takes a slug generator. It builds the prefix in front of the slug, formats what an editor types and fills an empty slug. Duplicate slugs can be extended with `-1`, `-2` [#159](https://github.com/open-dxp/opendxp/issues/159)
  > URL slug fields now support slug generators. A service of your own sets the prefix, formats what editors type and  fills in empty slugs. Duplicate slugs optionally get a `-1` or `-2` suffix.
- [BUGFIX] Saving a class checks the fields inside localized fields.
- [BUGFIX] Moving a page or a hardlink without a backend user, for example in a command, no longer fails in the redirect listener.
- [BUGFIX] A long-running process, such as a Messenger worker, caches items with a tag again after an earlier request cleared that tag. Before, `CoreCacheHandler::reset()` kept the cleared tags, and the process refused to cache them until it restarted.
- [BUGFIX] The `Cache` facade uses the cache handler of the current kernel. After a kernel reboot in the same process, it kept talking to the handler of the old kernel.
- [CHORE] The console no longer calls the deprecated `Application::add()` of Symfony 7.4.
- [BUGFIX] Removing the focal point of an image clears its thumbnails.
- [BUGFIX] A cover thumbnail respects a focal point on the left edge of an image.
- [CHORE] Require `league/csv` ^9.27
- [NEW FEATURE] `SettingsStoreAwareInstaller::updateEntitySchema()` brings the entity tables of a bundle to their mapping. See [Installers](doc/20_Extending_OpenDxp/13_Bundle_Developers_Guide/05_OpenDxp_Bundles/01_Installers.md).
- [NEW FEATURE] `SettingsStoreAwareInstaller::markMigrationsAsExecuted()` and `markMigrationsAsNotExecuted()` mark the migrations of a bundle during install and uninstall. See [Installers](doc/20_Extending_OpenDxp/13_Bundle_Developers_Guide/05_OpenDxp_Bundles/01_Installers.md).
- [DEPRECATION] `SettingsStoreAwareInstaller::getLastMigrationVersionClassName()`. It stays until 2.0 because of BC reasons. Call `markMigrationsAsExecuted()` in `install()` instead.
- [BUGFIX] Deleting an element removes the dependencies on it [#116](https://github.com/open-dxp/opendxp/issues/116)
- [BUGFIX] The dependency views list existing elements only [#116](https://github.com/open-dxp/opendxp/issues/116)
- [BUGFIX] Filtering "Required By" by path finds elements of every type [#116](https://github.com/open-dxp/opendxp/issues/116)
- [SECURITY] Mail placeholders and dynamic layout texts may only read objects. Allow more with `readable_classes` and `methods` in `opendxp.templating_engine.twig.sandbox_security_policy`.
- [SECURITY] Mail placeholders and dynamic layout texts can no longer call `opendxp_dump`.
- [CHORE] Require `twig/twig` ^3.29
- [BUGFIX] A mail placeholder that breaks the sandbox policy names what is not allowed.
- [BUGFIX] Saving a classification store key checks its definition. A key whose definition names an invalid field is not saved.
- [BUGFIX] Saving a classification store key writes the name of the key into its definition, so the key name follows the rule for field names too.
- [BUGFIX] A messenger worker sends the HTTP cache invalidations after each message, so a page that a scheduled task publishes reaches the proxy right away.
- [BUGFIX] A Twig placeholder in a link of the WYSIWYG editor keeps its spaces, for example `mailto:{{ email }}`. A link that already holds `{{%20email%20}}` is repaired the next time its editor is saved.
- [CHORE] Remove the Studio flag of notifications. `OpenDxp\Model\Notification::isStudio()` and `setIsStudio()` are gone.
- [NEW FEATURE] The Generic Execution Engine tracks the background work of a project as job runs. See [Track Background Work](doc/19_Development_Tools_and_Details/08_Generic_Execution_Engine/01_Jobs_and_Jobruns/03_Track_Background_Work.md).
- [NEW FEATURE] A job run hands over a result with files. An execution context with `disposable: true` deletes its job runs once their result is picked up. See [Configuration](doc/19_Development_Tools_and_Details/08_Generic_Execution_Engine/02_Configuration/02_Configuration.md).
- [NEW FEATURE] The maintenance deletes ended job runs after `delete_job_runs_older_than` and fails silent job runs after `fail_inactive_job_runs_after`. Both are off by default.
- [ENHANCEMENT] `stop_on_first_error` fails only the job run with the error. It no longer stops every Messenger worker.
- [NEW FEATURE] [HIGHLIGHT] The admin lists job runs under "Extras" > "Background Jobs". Users with the permission see the own job runs, cancel them and download their files.
  > OpenDXP tracks background work as job runs. The admin lists them under "Extras" > "Background Jobs", where users see their own runs, cancel them and download their files.
- [ENHANCEMENT] A step of a job also waits for the messages of its handlers, when they implement `JobRunMessageInterface`.
- [BUGFIX] Parallel workers no longer lose the progress of a job step.
- [BUGFIX] A message that its step condition skips counts once.
- [BUGFIX] `JobExecutionAgentInterface::rerunJobRun()` starts the job in the execution context of the old job run.
- [BUGFIX] A job run without owner no longer breaks `JobRunStateChangedEvent`.
- [BUGFIX] `JobRunErrorLogRepository::getLogsByJobRunId()` returns the logs of the given step.
- [BUGFIX] The engine sends a job message without routing to its own transport. The message no longer runs in the request.
- [DEPRECATION] `JobExecutionAgentInterface::continueJobMessageExecution()`. The engine counts the messages of a job run itself.
- [ENHANCEMENT] Every log entry names the user, the route and the console command. See [Logging](doc/19_Development_Tools_and_Details/07_Logging.md#context-of-a-log-entry) [#232](https://github.com/open-dxp/opendxp/issues/232)
- [ENHANCEMENT] When an element cannot be saved, the error says which element it is and why, for example `Object /products/shoe (ID 123) cannot be saved. Its parent 4711 does not exist.` See [Error Messages](doc/19_Development_Tools_and_Details/12_Error_Messages.md) [#232](https://github.com/open-dxp/opendxp/issues/232)
- [CHORE] [FOLLOW-UP] [MIGRATION] The update marks running job runs of the Generic Execution Engine as failed
  1. Only if your project uses the Generic Execution Engine: wait until no job run is running before you update.
- [NEW FEATURE] A data type can split its value into several grid columns with `GridColumnsInterface`. See [Grid Columns](doc/20_Extending_OpenDxp/13_Bundle_Developers_Guide/11_Adding_Object_Datatypes.md#grid-columns) [#253](https://github.com/open-dxp/opendxp/pull/253)
- [BUGFIX] A grid filtered by a column of a classification store counts its objects again [#253](https://github.com/open-dxp/opendxp/pull/253)
- [NEW FEATURE] `OpenDxp\Test\Elements::refresh()` loads an element again in a test. See [Writing Tests](doc/19_Development_Tools_and_Details/29_Testing/03_Writing_Tests.md#refresh-an-element) [#254](https://github.com/open-dxp/opendxp/pull/254)

## 1.4.3
- [BUGFIX] Static routes without their own `_locale` now use the language of the nearest document. Before, they used the default locale, so translations and generated links could be in the wrong language. This became visible with Symfony 7.4.17 [#197](https://github.com/open-dxp/opendxp/issues/197)
- [BUGFIX] An explicit `_locale` of a static route is no longer overwritten by the language of the nearest document [#199](https://github.com/open-dxp/opendxp/pull/199)
- [CHORE] Remove the abandoned `doctrine/annotations` dependency. If your project uses it directly, add it to your own composer.json [#196](https://github.com/open-dxp/opendxp/issues/196)

## 1.4.2
- [SECURITY] Validate DataObject field names against a strict identifier pattern and quote identifiers when building DDL, closing a SQL injection path via crafted class definition field names.
- [SECURITY] Anchor the class definition name/ID validation and quote table names built from a class ID, closing a SQL injection path in `Block` field loading.
- [SECURITY] `Asset\WebDAV\Tree::move()` and `Element\Service::getElementFromSession()` now verify what a deserialized element actually is before using it, instead of trusting it implicitly.
- [SECURITY] Restrict which classes the admin session token and `TmpStore` may deserialize into. Custom authenticators and objects stored via `TmpStore` may need a config entry after this update, see [Restrict Deserialization Classes](doc/19_Development_Tools_and_Details/10_Security_Authentication/06_Restrict_Deserialization_Classes.md).
- [BUGFIX] `JobRun#context` is now mapped as `json` instead of `text`, matching its `array` property type [#182](https://github.com/open-dxp/opendxp/issues/182)
- [BUGFIX] `PermissionService` reads the session on the first permission check instead of in its constructor, which broke requests that had already closed the session

## 1.4.1
- [BUGFIX] Fix NULL writes into NOT NULL columns in classificationstore configs [#179](https://github.com/open-dxp/opendxp/pull/179)
- [BUGFIX] Fix application log archive growing with duplicate rows [#185](https://github.com/open-dxp/opendxp/pull/185)
- [BUGFIX] Only resolve pretty URLs for documents that are still pages [#186](https://github.com/open-dxp/opendxp/pull/186)
- [BUGFIX] Fix pretty URL resolving to a document from another site [#187](https://github.com/open-dxp/opendxp/pull/187)
- [BUGFIX] Ignore pretty URLs when checking whether a document path exists [#188](https://github.com/open-dxp/opendxp/pull/188)

## 1.4.0
- [NEW FEATURE] Tag-based http cache invalidation [#153](https://github.com/open-dxp/opendxp/pull/153)
- [NEW FEATURE] Introduce Permission Voters (User / Element) [#171](https://github.com/open-dxp/opendxp/pull/171)
- [ENHANCEMENT] Add indexed relation lookup as opt-in fast path, keep LIKE fallback unchanged [#169](https://github.com/open-dxp/opendxp/pull/169)
- [ENHANCEMENT] Performance: reduce queries and allocations in element loading and caching [@Cruiser13](https://github.com/open-dxp/opendxp/pull/162)
- [BUGFIX] Make thumbnail generation errors in PublicServicesController more actionable by logging additional information [@NiklasBr](https://github.com/open-dxp/opendxp/pull/161)
- [BUGFIX] Nack failed asset preview thumbnail generation instead of silent ack [@Cruiser13](https://github.com/open-dxp/opendxp/pull/163)
- [BUGFIX] Recycle Bin: Avoid rmdir on storage root [#167](https://github.com/open-dxp/opendxp/pull/167)
- [BUGFIX] `ImageThumbnailInterface::getAsset()` and `ImageThumbnailTrait::getAsset()` now declare a nullable return type (`?Asset` instead of `Asset`), matching the already-nullable `$asset` property. A thumbnail built without a backing asset (e.g. from a raw path reference) previously threw a `TypeError` because `getAsset()` returned `null` while promising a non-nullable `Asset`. Note: this widens the return type of a public API method to nullable — callers must now handle a possible `null` return value [@blankse](https://github.com/open-dxp/opendxp/pull/166)
- [BUGFIX] Make invisible fields in field-collections editable [@Simon-Drohsen](https://github.com/open-dxp/opendxp/pull/175)
- [BUGFIX] Restore ReverseObjectRelation `ownerClassId` on definition import [@alexloetscher95](https://github.com/open-dxp/opendxp/pull/177)
- [CHORE] Deprecate NotificationServiceFilterParser class [#164](https://github.com/open-dxp/opendxp/pull/164)

## 1.3.3
- [CHORE] Fix infinite-loop typo and stale routing docblock [#156](https://github.com/open-dxp/opendxp/pull/156)
- [CHORE] Refactor composite index generation, permission checks, and serialization handling [#155](https://github.com/open-dxp/opendxp/pull/155)
- [CHORE] Optimized composite index handling with static callbacks and safer query parameterization
- [CHORE] Improved WebDAV asset permission checks for rename, move, and overwrite actions
- [CHORE] Standardized serialization/unserialization with Serialize utility throughout the codebase
- [CHORE] Introduced InvalidQueryException for safer query validation in custom reports
- [CHORE] Added isAllowedForUser validation for report configuration access
- [CHORE] Enhanced CustomReportController with reusable loadReport and isValidConfigName methods, reducing duplicate logic

## 1.3.2
- [ENHANCEMENT] `UnmanagedTablesSchemaFilter` (formally `IgnoreCoreTablesFilterListener`) is now disabled by default and only activates during `doctrine:schema:update`, `doctrine:schema:validate`, and commands implementing `ExcludesUnmanagedTablesInterface`
- [NEW FEATURE] If `ExcludesUnmanagedTablesInterface` is implemented on any console command that calls `SchemaTool::updateSchema()` directly to prevent unintended DROP TABLE statements for non-ORM tables, see [docs](doc/19_Development_Tools_and_Details/06_Doctrine_Schema_Filter.md)
- [ENHANCEMENT] Add `forceResize` parameter to video scaling methods
- [ENHANCEMENT] Update `Ffmpeg` processing for improved format handling and codec support
- [ENHANCEMENT] Migrate Twig extensions to attributes
- [BUGFIX] Return exact language match before null-language fallback in `Asset::getMetadata()`
- [ENHANCEMENT] Collect Dateformats and centalize them in a single location with documentation

## 1.3.1
- [BUGFIX] Centralize brickPrefix handling in RelationFilterConditionParser [#120](https://github.com/open-dxp/opendxp/pull/120)
- [BUGFIX] Fix translation caching bugs and deprecate unused listing methods [#123](https://github.com/open-dxp/opendxp/pull/123)
- [ENHANCEMENT] PHP 8.5 support added
- [ENHANCEMENT] Remove more deprecated code usages [#112](https://github.com/open-dxp/opendxp/pull/112), [#113](https://github.com/open-dxp/opendxp/pull/113)
- [ENHANCEMENT] Refactor findForbiddenPaths to improve performance, combining user and role queries into a single optimized query [#115](https://github.com/open-dxp/opendxp/pull/115)
- [ENHANCEMENT] Compatibility fix with Symfony UX TwigComponent/LiveComponent
- [ENHANCEMENT] Improve path and key validation with explicit encoding and tests [#128](https://github.com/open-dxp/opendxp/pull/128)
- [ENHANCEMENT] Use Request Context for improved URL handling [#127](https://github.com/open-dxp/opendxp/pull/127)
- [ENHANCEMENT] Remove POEditor support and normalize translation files [#129](https://github.com/open-dxp/opendxp/pull/129)
- [ENHANCEMENT] Fix cross-site document path resolution in hardlink context [#132](https://github.com/open-dxp/opendxp/pull/132)

## 1.3.0
- [BUGFIX] Fix Video Processor when TmpStore is missing
- [ENHANCEMENT] Use try-finally for disable/enable versioning
- [ENHANCEMENT] Refactor SQL queries: enforce parameterized bindings and consistent style
- [ENHANCEMENT] Refactor constants
- [NEW FEATURE] Add site custom settings
- [NEW FEATURE] Introduce `GeneralHostResolver` service for centralized domain resolution, see [docs](doc/02_MVC/04_Routing_and_URLs/06_Domain_and_Host_Handling.md)

## 1.2.2
- [BUGFIX] Update upgrade notes and handle migration cleanup in CoreBundle
- [BUGFIX] Update IgnoreCoreTablesFilterListener

## 1.2.1
- [ENHANCEMENT] DataObject\Listing: Add missing PhpDocs
- [BUGFIX] Snippets will be cached with wrong links
- [BUGFIX] Make translations sortable by dates
- [ENHANCEMENT] `Translator::checkForEmptyTranslation()` - getValidLanguages needs to accept domain parameter
- [ENHANCEMENT] Use preferred tile.openstreetmap.org URL
- [ENHANCEMENT] Add IgnoreCoreTablesFilterListener
- [ENHANCEMENT] Improve Generic Execution Engine Entities & Manager
- [ENHANCEMENT] [ApplicationLogger] standardize quotes and improve code readability in js files
- [ENHANCEMENT] Update FOSJsRoutingBundle routing resource to use PHP configuration
- [ENHANCEMENT] Replace deprecated Doctrine DBAL query introspection methods
- [ENHANCEMENT] Update version references

## 1.2.0
- [ENHANCEMENT] Symfony 7.4 compatibility (bumped minimum required version to `^7.4`)
- [ENHANCEMENT] Code cleanup, performance improvements & modernized code style.
- [DEPRECATION] `lib/helper-functions.php`. Use static helpers from `OpenDxp\Helper\*` instead.
- [ENHANCEMENT] AdminBundle: Various UI refinements like colors, icons and some general styling
- [ENHANCEMENT] AdminBundle: Symfony 7.4 compatibility
- [ENHANCEMENT] AdminBundle: Code cleanup, performance improvements & modernized code style.
- [NEW FEATURE] AdminBundle: Added poster image, title and description for every video type (video overlay)

## 1.1.5
- [CHORE] Remove outdated images from documentation [#30](https://github.com/open-dxp/opendxp/pull/30)
- [CHORE] Remove outdated book reference from documentation [#31](https://github.com/open-dxp/opendxp/pull/31)
- [ENHANCEMENT] Improve error-handling during json class-import [#32](https://github.com/open-dxp/opendxp/pull/32)
- [CHORE] Updated to 11.5.12 [#33](https://github.com/open-dxp/opendxp/pull/33)

## 1.1.4
- [CHORE] Docs: add some further upgrade notes [#27](https://github.com/open-dxp/opendxp/pull/27)
- [BUGFIX] Correct value for `OpenDxp\Version::MAJOR_VERSION` [#28](https://github.com/open-dxp/opendxp/pull/28)
- [ENHANCEMENT] Improve env variable based domain check [#29](https://github.com/open-dxp/opendxp/pull/29)

## 1.1.3
- [ENHANCEMENT] BlockElement: Add SetNullFilter for '_owner' property in DeepCopy configuration to improve performance.

## 1.1.2
- [CHORE] Updated from base 11.5.10 [#24](https://github.com/open-dxp/opendxp/pull/24)

## 1.1.1
- [CHORE] Update branch aliases [#17](https://github.com/open-dxp/opendxp/pull/17)
- [CHORE] Update `admin-ui-classic-bundle` references [#18](https://github.com/open-dxp/opendxp/pull/18)
- [CHORE] Update readme [#22](https://github.com/open-dxp/opendxp/pull/22)
- [CHORE] Updated from base 11.5.9 [#23](https://github.com/open-dxp/opendxp/pull/23)

## 1.1.0
- [CHORE] Added `open-dxp/admin-bundle` as core dependency since this is currently the only backend UI available.
- [CHORE] [FOLLOW-UP] AdminBundle: Renamed `open-dxp/admin-ui-classic-bundle` to `open-dxp/admin-bundle`. The PHP namespace has not changed.
  1. Only if your composer.json requires `open-dxp/admin-ui-classic-bundle`: replace it with `open-dxp/admin-bundle`.
- [CHORE] AdminBundle: Abandoned composer package `open-dxp/admin-ui-classic-bundle`.
- [ENHANCEMENT] SeoBundle: Performance improvements for redirect resolving in `RoutingListener`.

## 1.0.0
- [NEW FEATURE] Initial release
- [CHORE] Phpstan: remove 8.1 support [#1](https://github.com/open-dxp/opendxp/pull/1)
- [CHORE] Fix issue templates, start migration documentation [#2](https://github.com/open-dxp/opendxp/pull/2)
- [CHORE] Adopt latest changes from base [#3](https://github.com/open-dxp/opendxp/pull/3)
- [ENHANCEMENT] PHP 8.4 & Symfony 7.3 [#4](https://github.com/open-dxp/opendxp/pull/4)
- [CHORE] Improve doc, add layout adoptions [#5](https://github.com/open-dxp/opendxp/pull/5)
- [BUGFIX] Fixes [#6](https://github.com/open-dxp/opendxp/pull/6)
- [CHORE] Adjust test matrix [#7](https://github.com/open-dxp/opendxp/pull/7)
- [CHORE] Adjust docs [#8](https://github.com/open-dxp/opendxp/pull/8)
