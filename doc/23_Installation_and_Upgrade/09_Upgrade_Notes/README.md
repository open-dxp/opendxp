# Upgrade Notes
TBD

## Migrating from Pimcore to OpenDXP

### Preparation
Upgrade to the latest Pimcore `11.5.x` first!

### Settings Store
- In table `settings_store`, replace `BUNDLE_INSTALLED__Pimcore` with `BUNDLE_INSTALLED__OpenDxp`

### Methods
- getPimcoreUser => getOpenDxpUser

### Configuration
- Autoload directory: config/pimcore => config/opendxp
- All Pimcore configuration blocks `pimcore_*` (like pimcore_admin) => opendxp_*`

### Constants
- All Constants `PIMCORE_` => `OPENDXP_`

### Messenger
- All runners from `pimcore_` => `opendxp_`

### Commands
- All commands from `pimcore:` => `opendxp:`

### Twig
- All twig methods changed from `pimcore_` => `opendxp_`

***

## Breaking Changes

### Wysiwyg
The suggested Quill editor lacks the capabilities expected in a professional CMS.
As a result, we’ve reintroduced the TinyMCE bundle. (TinyMCE has changed its open-source license to GPL, which is acceptable for our use.)

### Misc
 - Core: Removed PlatformVersion
 - Skeleton: Removed QuillBundle

## Migrations
TBD