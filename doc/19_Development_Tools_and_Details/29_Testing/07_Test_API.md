# Test API

OpenDXP ships factories and Pest expectations for the tests of projects and bundles, in the namespace
`OpenDxp\Test`. This page lists them. [Factories and Stories](./05_Factories_and_Stories.md) explains
how to use them and how to write factories of your own.

## Factories

Every factory is in `OpenDxp\Test\Factory`. A factory fills every value a test leaves out with random
data.

| Factory                       | Builds                         |
|-------------------------------|--------------------------------|
| `AssetDocumentFactory`        | `Asset\Document`               |
| `AssetFolderFactory`          | `Asset\Folder`                 |
| `AssetImageFactory`           | `Asset\Image`                  |
| `AssetVideoFactory`           | `Asset\Video`                  |
| `DataObjectFolderFactory`     | `DataObject\Folder`            |
| `DocumentEmailFactory`        | `Document\Email`               |
| `DocumentFolderFactory`       | `Document\Folder`              |
| `DocumentHardlinkFactory`     | `Document\Hardlink`            |
| `DocumentLinkFactory`         | `Document\Link`                |
| `DocumentPageFactory`         | `Document\Page`                |
| `DocumentSnippetFactory`      | `Document\Snippet`             |
| `GlossaryFactory`             | `Glossary`                     |
| `QuantityValueUnitFactory`    | `QuantityValue\Unit`           |
| `RedirectFactory`             | `Redirect`                     |
| `SiteFactory`                 | `Site`                         |
| `StaticRouteFactory`          | `Staticroute`                  |
| `TagFactory`                  | `Element\Tag`                  |
| `ThumbnailConfigFactory`      | `Asset\Image\Thumbnail\Config` |
| `TranslationFactory`          | `Translation`                  |
| `UserFactory`                 | `User`                         |
| `UserRoleFactory`             | `User\Role`                    |
| `VideoThumbnailConfigFactory` | `Asset\Video\Thumbnail\Config` |
| `WebsiteSettingFactory`       | `WebsiteSetting`               |

`GlossaryFactory`, `RedirectFactory` and `StaticRouteFactory` build models of the glossary, SEO and
static routes bundles. They need that bundle in the kernel of your test application.

## States

A state returns the factory, so states chain. These states belong to a kind of model:

- Every factory: `unsaved()` builds the model without writing it.
- Assets, documents and data objects: `withParent()`, `withProperty()`, `withInheritableProperty()`.
- Documents: `unpublished()`, `withLocale()`, `withTranslationOf()`, `withNavigationName()`.
- Pages, snippets and emails: `withController()`, `withContentMainDocument()`, `withEditables()`,
  `withBricks()`.
- Data objects: `unpublished()`, `withLocalizedValues()`, `withFieldcollection()`, `withObjectbrick()`,
  `withLocalizedObjectbrick()`, `withClassificationValues()`, `withLocalizedClassificationValues()`.
  They come with `AbstractDataObjectFactory`, the base of the factories for your own classes.
- Users and roles: `withPermissions()`, `withAssetWorkspace()`, `withDocumentWorkspace()`,
  `withObjectWorkspace()`.

These states belong to one factory:

- `AssetVideoFactory`: `convertedTo()`.
- `DocumentHardlinkFactory`: `withSource()`.
- `DocumentLinkFactory`: `withTarget()`.
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

Register the expectations in `tests/Pest.php`:

```php
use OpenDxp\Test\Expectation\Fields;
use OpenDxp\Test\Expectation\Redirects;

Fields::register();
Redirects::register();
```

| Expectation               | Checks                                                                |
|---------------------------|-----------------------------------------------------------------------|
| `toCarryField()`          | A data object carries the value in the field.                         |
| `toCarryLocalizedField()` | A data object carries the value in one language of a localized field. |
| `toRedirectTo()`          | A response redirects to the location.                                 |
| `toComeFrom()`            | A redirect answered the request.                                      |
| `toComeFromNoRedirect()`  | No redirect answered the request.                                     |

`Redirects` needs the SEO bundle in the kernel of your test application.
