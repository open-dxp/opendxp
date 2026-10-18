# Test API

OpenDXP ships factories and Pest expectations for the tests of projects and bundles, in the namespace
`OpenDxp\Test`. This page lists them. [Factories and Stories](./05_Factories_and_Stories.md) explains
how to use them and how to write factories of your own.

## Registration

Pest knows an expectation only after it is registered. Register the expectations in `tests/Pest.php`:

```php
use OpenDxp\Test\Expectation\Fields;
use OpenDxp\Test\Expectation\Redirects;

Fields::register();
Redirects::register();
```

`Redirects` needs `OpenDxpSeoBundle` in the test kernel. Leave it out if you do not test redirects.

## Factories

Every factory is in `OpenDxp\Test\Factory`. A factory fills every value a test leaves out with random
data.

| Factory                       | Builds                            |
|-------------------------------|-----------------------------------|
| `AssetDocumentFactory`        | `Asset\Document`                  |
| `AssetFolderFactory`          | `Asset\Folder`                    |
| `AssetImageFactory`           | `Asset\Image`                     |
| `AssetVideoFactory`           | `Asset\Video`                     |
| `CustomReportFactory`         | `CustomReportsBundle\Tool\Config` |
| `DataObjectFolderFactory`     | `DataObject\Folder`               |
| `DocumentEmailFactory`        | `Document\Email`                  |
| `DocumentFolderFactory`       | `Document\Folder`                 |
| `DocumentHardlinkFactory`     | `Document\Hardlink`               |
| `DocumentLinkFactory`         | `Document\Link`                   |
| `DocumentPageFactory`         | `Document\Page`                   |
| `DocumentSnippetFactory`      | `Document\Snippet`                |
| `EmailLogFactory`             | `Tool\Email\Log`                  |
| `GlossaryFactory`             | `Glossary`                        |
| `QuantityValueUnitFactory`    | `QuantityValue\Unit`              |
| `RedirectFactory`             | `Redirect`                        |
| `SiteFactory`                 | `Site`                            |
| `StaticRouteFactory`          | `Staticroute`                     |
| `TagFactory`                  | `Element\Tag`                     |
| `ThumbnailConfigFactory`      | `Asset\Image\Thumbnail\Config`    |
| `TranslationFactory`          | `Translation`                     |
| `UserFactory`                 | `User`                            |
| `UserRoleFactory`             | `User\Role`                       |
| `VideoThumbnailConfigFactory` | `Asset\Video\Thumbnail\Config`    |
| `WebsiteSettingFactory`       | `WebsiteSetting`                  |

`CustomReportFactory`, `GlossaryFactory`, `RedirectFactory` and `StaticRouteFactory` build models of the custom
reports, glossary, SEO and static routes bundles. They need that bundle in the kernel of your test application.

### Base classes

A factory of your own extends one of these base classes and inherits its states:

| Base class                   | Builds                                  |
|------------------------------|-----------------------------------------|
| `AbstractSavingFactory`      | any model that is written with `save()` |
| `AbstractElementFactory`     | assets, documents and data objects      |
| `AbstractDocumentFactory`    | documents                               |
| `AbstractPageSnippetFactory` | pages, snippets and emails              |
| `AbstractDataObjectFactory`  | data objects of your own classes        |
| `AbstractUserRoleFactory`    | users and roles                         |

### Fixture files

The asset factories fill an asset with a file from `lib/Test/Fixtures`. The static method `fixture()`
returns its path:

| Factory                | File           |
|------------------------|----------------|
| `AssetDocumentFactory` | `document.pdf` |
| `AssetImageFactory`    | `image.jpg`    |
| `AssetVideoFactory`    | `video.mp4`    |

## States

A state returns the factory, so states chain. These states belong to a kind of model:

- Every factory: `unsaved()` builds the model without writing it.
- Assets, documents and data objects: `withParent()`, `withProperty()`, `withInheritableProperty()`.
- Documents: `unpublished()`, `withLocale()`, `withTranslationOf()`, `withNavigationName()`.
- Pages, snippets and emails: `withController()`, `withContentMainDocument()`, `withEditables()`,
  `withBricks()`.
- Data objects: `unpublished()`, `withLocalizedValues()`, `withFieldcollection()`, `withObjectbrick()`,
  `withLocalizedObjectbrick()`, `withClassificationValues()`, `withLocalizedClassificationValues()`.
- Users and roles: `withPermissions()`, `withAssetWorkspace()`, `withDocumentWorkspace()`,
  `withObjectWorkspace()`.

These states belong to one factory:

- `AssetImageFactory`: `withMetadata()`.
- `AssetVideoFactory`: `convertedTo()`.
- `CustomReportFactory`: `withSqlDataSource()`.
- `DocumentHardlinkFactory`: `withSource()`.
- `DocumentLinkFactory`: `withTarget()`.
- `EmailLogFactory`: `forDocument()`, `sentAt()`.
- `RedirectFactory`: `forDomain()`, `matching()`, `forSite()`, `toSite()`, `toDocument()`,
  `withStatusCode()`, `withPriority()`, `passingThroughPath()`, `passingThroughParameters()`,
  `protected()`, `inactive()`, `started()`, `scheduled()`, `expiring()`, `expired()`.
- `SiteFactory`: `withRoot()`, `withDomains()`, `withErrorDocument()`, `withLocalizedErrorDocuments()`,
  `withCustomSettings()`.
- `StaticRouteFactory`: `withController()`, `withPattern()`.
- `TagFactory`: `withParent()`, `assignedTo()`.
- `ThumbnailConfigFactory`: `scalingByWidth()`, `enlargingToWidth()`, `covering()`, `rotating()`.
- `TranslationFactory`: `withTranslations()`, `inAdminDomain()`.
- `UserFactory`: `admin()`, `withRoles()`, `withPassword()`.
- `VideoThumbnailConfigFactory`: `scalingByWidth()`.
- `WebsiteSettingFactory`: `forSite()`, `inLanguage()`.

## Expectations

| Expectation                     | Registered by | Checks                                                                |
|---------------------------------|---------------|-----------------------------------------------------------------------|
| `toCarryField()`                | `Fields`      | A data object carries the value in the field.                         |
| `toCarryLocalizedField()`       | `Fields`      | A data object carries the value in one language of a localized field. |
| `toRedirectTo()`                | `Redirects`   | A response redirects to the location.                                 |
| `toBeAnsweredBy()`              | `Redirects`   | A redirect answered the request.                                      |
| `toBeAnsweredWithoutRedirect()` | `Redirects`   | No redirect answered the request.                                     |

## Installers

An installer creates a definition from a JSON file, or updates the definition if it exists:

```php
use OpenDxp\Test\ClassDefinitions;
use OpenDxp\Test\ClassificationStores;
use OpenDxp\Test\Fieldcollections;
use OpenDxp\Test\ObjectBricks;

ClassificationStores::install('PostFacts', $file);
Fieldcollections::install('PostLink', $file);
ClassDefinitions::install('Post', $file);
ObjectBricks::install('PostRating', $file);
```

You export a class, a field collection or an object brick from its editor. A classification store
has a file format of its own. The file describes the store, its groups and the keys of each
group. The type of a key is the name of a field type:

```json
{
    "description": "The store a post keeps its facts in.",
    "groups": {
        "facts": {
            "readingTime": {
                "type": "numeric",
                "description": "Minutes it takes to read the post."
            }
        }
    }
}
```

The test foundation installs the definitions in `tests/Fixtures` with these installers, as
[Data object classes](https://github.com/open-dxp/test-foundation#data-object-classes) describes.

## Helpers

| Helper                                        | What it does                                                                               |
|-----------------------------------------------|--------------------------------------------------------------------------------------------|
| `Elements::refresh($element)`                 | Clears the runtime cache and loads the element from the database again.                    |
| `Settings::override($settings)`               | Replaces system settings until the test ends.                                              |
| `Document\Brick`                              | The interface of a brick. `id()` names the areabrick, `editables()` returns its editables. |
| `Document\Areablocks::filledWith($name, ...)` | Returns the editables of an areablock that holds the given bricks.                         |

## Deprecated

| Class                         | Use instead                              |
|-------------------------------|------------------------------------------|
| `OpenDxp\Test\KernelTestCase` | `OpenDxp\TestFoundation\TestCase`        |
| `OpenDxp\Test\WebTestCase`    | `OpenDxp\TestFoundation\BrowserTestCase` |

Both are removed in OpenDXP 2.0.
