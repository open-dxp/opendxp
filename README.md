<img src="./doc/img/logo-readme.png" alt="OpenDXP" width="60%" />

Open Source Data & Digital Experience Platform

***

## Disclaimer

> OpenDXP is a community-driven fork based on the Pimcore® Community Edition (GPLv3).  
> OpenDXP is independent and maintained by its community and contributors. 
> It is not affiliated with, endorsed by, or sponsored by Pimcore GmbH.   
> Original credits: [Pimcore GmbH](https://www.pimcore.com)

**OpenDXP is based on the Pimcore® Community Edition and remains licensed under GPLv3.**

***

[![Packagist](https://img.shields.io/packagist/v/open-dxp/opendxp.svg)](https://packagist.org/packages/open-dxp/opendxp)
[![Software License](https://img.shields.io/badge/license-GPLv3-brightgreen.svg?style=flat)](LICENSE.md)
[![Gitter](https://img.shields.io/badge/gitter-join%20chat-brightgreen.svg?style=flat)](https://gitter.im/open-dxp/opendxp)

* [Get started with OpenDXP 1.0](doc/23_Installation_and_Upgrade/09_Upgrade_Notes/README.md#get-started-with-opendxp-10)
* [Documentation](doc)
* [Issue Tracker](https://github.com/open-dxp/opendxp/issues) - Report bugs or suggest new features
* [Forums](https://github.com/orgs/open-dxp/discussions) - Community support and discussions

## Contribute  
**Bug fixes:** please create a pull request including a step by step description to reproduce the problem  
**Security vulnerabilities:** please see our [security policy](https://github.com/open-dxp/opendxp/security/policy)
  
## Core Features and Highlights
OpenDXP provides a codebase that enables: 

### Simultaneous Data Modeling and UI Configuration 
OpenDXP supports defining data structures (e.g., MDM/PIM) and configuring editorial UIs in parallel.
Unstructured web content can be handled via templates; structured data can be managed with a graphical class editor. 
Data is persisted by the application according to the project’s configuration. 

### Flexible Framework and Extensibility 
OpenDXP uses the Symfony framework.
Functionality can be extended via Symfony components and custom bundles. 
APIs allow integration with various frontend stacks. 

### Combined Functional Areas 
The application includes functionality for MDM/PIM, DAM, and Web‑CMS within the same codebase.
Depending on solution architecture, this setup can reduce additional integration work (e.g., separate APIs, import/export, synchronization). 

### Administration Interface 
The administration interface is available for editorial workflows. 
Configuration options can be adjusted to align UI behavior with project requirements. 

***

## Getting Started
_**Only three commands to start!**_

```bash
COMPOSER_MEMORY_LIMIT=-1 composer create-project open-dxp/skeleton ./my-project
cd ./my-project
./vendor/bin/opendxp-install
```

This will install an empty skeleton application. [Click here for more installation options and a detailed guide](doc/01_Getting_Started/README.md)

## Supported Versions
Support of a minor version of OpenDXP packages ends with the release of the next minor version.

***

## Upstream Origin & Version Transparency 
This project is a fork of the [Pimcore® Community Edition (4f6ca98 / v11.5.10)](https://github.com/pimcore/pimcore/tree/4f6ca98ca97e56eed4ace601a551b43ceea1e192), which is © Pimcore GmbH and licensed under GPLv3. 

## License 
Licensed under the GNU General Public License v3.0 (GPLv3). For details, please see [LICENSE.md](LICENSE.md). 

## Copyright 
© Pimcore GmbH  
© 2025 OpenDXP Contributors — GPLv3 

## Trademarks 
Pimcore® is a registered [trademark](https://www.trademarkelite.com/europe/trademark/trademark-detail/009309841/PIMCORE) of Pimcore GmbH. 
Any use of the Pimcore® mark in this repository is purely descriptive to identify the original upstream project. 

***

## Contact
For inquiries, suggestions, or contributions, feel free to reach us at contact@opendxp.ch.

## About
OpenDXP is a community-driven project initiated by [DACHCOM.DIGITAL](https://www.dachcom.com/de-ch) (Rheineck, Switzerland) and maintained by its community and contributors. 
OpenDXP is independent and not affiliated with Pimcore GmbH. 

The project’s purpose is to preserve and maintain a GPLv3‑licensed codebase for community use.   

It is **not positioned as a competitor** to products or services of Pimcore GmbH and does **not** purport to replace or supersede any Pimcore offering.   
