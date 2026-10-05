<img src="./doc/img/logo-readme.png" alt="OpenDXP" width="60%" />

Open Source Data & Digital Experience Platform

[![Packagist](https://img.shields.io/packagist/v/open-dxp/opendxp.svg)](https://packagist.org/packages/open-dxp/opendxp)
[![Software License](https://img.shields.io/badge/license-GPLv3-brightgreen.svg?style=flat)](LICENSE.md)

***

## Disclaimer

> OpenDXP is a community-driven fork based on the Pimcore® Community Edition (GPLv3).  
> OpenDXP is independent and maintained by its community and contributors. 
> It is not affiliated with, endorsed by, or sponsored by Pimcore GmbH.   
> Original credits: [Pimcore GmbH](https://www.pimcore.com)

**OpenDXP is based on the Pimcore® Community Edition and remains licensed under GPLv3.**

***

<h3>Open source platform combining PIM · DAM · Headless CMS · Composable Commerce</h3>

API-first, built on Symfony — 100% open source, community-driven.

<table>
  <tr>
    <td valign="top" width="33%">
      <br>
      <picture>
        <source srcset="./doc/img/icon-core-framework-dark.svg" media="(prefers-color-scheme: dark)" />
        <source srcset="./doc/img/icon-core-framework.svg" media="(prefers-color-scheme: light), (prefers-color-scheme: no-preference)" />
        <img src="./doc/img/icon-core-framework.svg" alt="" />
      </picture><br>
      <h4>Core Framework</h4>
      <p>Installation, configuration and the complete API reference for the OpenDXP platform.</p>
      <a href="https://docs.opendxp.io/docs/core-framework">Browse Documentation →</a><br><br>
    </td>
    <td valign="top" width="33%">
      <br>
      <picture>
        <source srcset="./doc/img/icon-core-bundles-dark.svg" media="(prefers-color-scheme: dark)" />
        <source srcset="./doc/img/icon-core-bundles.svg" media="(prefers-color-scheme: light), (prefers-color-scheme: no-preference)" />
        <img src="./doc/img/icon-core-bundles.svg" alt="" />
      </picture><br>
      <h4>Core Bundles</h4>
      <p>Official bundles extending the core: Search, Marketing, Data Hub and more.</p>
      <a href="https://docs.opendxp.io/docs/core-bundles">Browse Documentation →</a><br><br>
    </td>
    <td valign="top" width="33%">
      <br>
      <picture>
        <source srcset="./doc/img/icon-feature-bundles-dark.svg" media="(prefers-color-scheme: dark)" />
        <source srcset="./doc/img/icon-feature-bundles.svg" media="(prefers-color-scheme: light), (prefers-color-scheme: no-preference)" />
        <img src="./doc/img/icon-feature-bundles.svg" alt="" />
      </picture><br>
      <h4>Feature Bundles</h4>
      <p>Empowering feature bundles: FormBuilder, Headless, E-Commerce and many more.</p>
      <a href="https://docs.opendxp.io/docs/bundles">Browse Documentation →</a><br><br>
    </td>
  </tr>
</table>

---

## Core Features and Highlights
OpenDXP provides a codebase that enables:

<h3><picture><source srcset="./doc/img/icon-data-modeling-dark.svg" media="(prefers-color-scheme: dark)" /><source srcset="./doc/img/icon-data-modeling.svg" media="(prefers-color-scheme: light), (prefers-color-scheme: no-preference)" /><img src="./doc/img/icon-data-modeling.svg" width="20" height="20" alt="" /></picture>&nbsp; Simultaneous Data Modeling and UI Configuration</h3>

OpenDXP supports defining data structures (e.g., MDM/PIM) and configuring editorial UIs in parallel.
Unstructured web content can be handled via templates; structured data can be managed with a graphical class editor.
Data is persisted by the application according to the project's configuration.

<h3><picture><source srcset="./doc/img/icon-extensibility-dark.svg" media="(prefers-color-scheme: dark)" /><source srcset="./doc/img/icon-extensibility.svg" media="(prefers-color-scheme: light), (prefers-color-scheme: no-preference)" /><img src="./doc/img/icon-extensibility.svg" width="20" height="20" alt="" /></picture>&nbsp; Flexible Framework and Extensibility</h3>

OpenDXP uses the Symfony framework.
Functionality can be extended via Symfony components and custom bundles.
APIs allow integration with various frontend stacks.

<h3><picture><source srcset="./doc/img/icon-combined-areas-dark.svg" media="(prefers-color-scheme: dark)" /><source srcset="./doc/img/icon-combined-areas.svg" media="(prefers-color-scheme: light), (prefers-color-scheme: no-preference)" /><img src="./doc/img/icon-combined-areas.svg" width="20" height="20" alt="" /></picture>&nbsp; Combined Functional Areas</h3>

The application includes functionality for MDM/PIM, DAM, and Web‑CMS within the same codebase.
Depending on solution architecture, this setup can reduce additional integration work (e.g., separate APIs, import/export, synchronization).

<h3><picture><source srcset="./doc/img/icon-admin-dark.svg" media="(prefers-color-scheme: dark)" /><source srcset="./doc/img/icon-admin.svg" media="(prefers-color-scheme: light), (prefers-color-scheme: no-preference)" /><img src="./doc/img/icon-admin.svg" width="20" height="20" alt="" /></picture>&nbsp; Administration Interface</h3>

The administration interface is provided by the [admin-bundle](https://github.com/open-dxp/admin-bundle) and available for editorial workflows.
Configuration options can be adjusted to align UI behavior with project requirements.

---

## Getting Started

Three commands to install a working skeleton application:

```bash
COMPOSER_MEMORY_LIMIT=-1 composer create-project open-dxp/skeleton ./my-project
cd ./my-project
./vendor/bin/opendxp-install
```

[Full installation guide →](https://docs.opendxp.io/docs/core-framework/Getting_Started/)

---

## Resources

|                                                                                                                                  |                                           |
|----------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------|
| [Documentation](https://docs.opendxp.io/)                                                                                        | Full platform & bundle docs               |
| [Issue Tracker](https://github.com/open-dxp/opendxp/issues)                                                                      | Report bugs or request features           |
| [Discussions](https://github.com/orgs/open-dxp/discussions)                                                                      | Community support                         |
| [Upgrade Notes](https://docs.opendxp.io/docs/core-framework/Installation_and_Upgrade/Upgrade_Notes/#get-started-with-opendxp-10) | Get started with OpenDXP 1.0              |
| [Testing](doc/19_Development_Tools_and_Details/29_Testing/README.md)                                                             | Test your project, bundle or OpenDXP itself |

## Contributing

**Bug fixes:** open a pull request including a step-by-step description to reproduce the problem.  
**Security vulnerabilities:** see our [security policy](https://github.com/open-dxp/opendxp/security/policy).

### Translations
Translation files live in `bundles/*Bundle/translations/` as YAML files (see [CoreBundle](bundles/CoreBundle/translations) for example).

- The English source file is `translations/admin.en.yaml`
- Each language has its own file, e.g. `admin.de.yaml`, `admin.fr.yaml`
- To improve an existing translation: edit the relevant file and open a pull request
- To add a new language: copy `admin.en.yaml`, rename it to `admin.<locale>.yaml`, translate the values, and open a pull request


## Supported Versions
Support of a minor version of OpenDXP packages ends with the release of the next minor version.

***

## Upstream Origin & Version Transparency 
This project is a fork of the [Pimcore® Community Edition (9246a42 / v11.5.13)](https://github.com/pimcore/pimcore/tree/9246a42d914fc3f75c9d90bec7b946a561e7be6f), which is © Pimcore GmbH and licensed under GPLv3. 

## License 
Licensed under the GNU General Public License v3.0 (GPLv3). For details, please see [LICENSE.md](LICENSE.md). 

## Copyright 
© Pimcore GmbH  
© 2026 OpenDXP Contributors — GPLv3 

## Trademarks 
Pimcore® is a registered [trademark](https://www.trademarkelite.com/europe/trademark/trademark-detail/009309841/PIMCORE) of Pimcore GmbH. 
Any use of the Pimcore® mark in this repository is purely descriptive to identify the original upstream project. 

***

## Contact
For inquiries, suggestions, or contributions, feel free to reach us at contact@opendxp.io.

## About
OpenDXP is a community-driven project initiated by [DACHCOM.DIGITAL](https://www.dachcom.com/de-ch) (Rheineck, Switzerland) and maintained by its community and contributors. 
OpenDXP is independent and not affiliated with Pimcore GmbH. 

The project’s purpose is to preserve and maintain a GPLv3‑licensed codebase for community use.   

It is **not positioned as a competitor** to products or services of Pimcore GmbH and does **not** purport to replace or supersede any Pimcore offering.   
