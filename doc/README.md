# OpenDXP Core Framework Documentation

> This section provides everything developers need to work with the OpenDXP Core Framework.  
> It is aimed specifically at a technical audience.

OpenDXP offers a fully flexible and extensible platform for managing and utilizing data of any type.  
The Core Framework forms the foundation of the entire platform and delivers essential basic functionalities.

It consists of four main modules, covering a wide range of use cases:  
- Product Information Management (PIM) and Master Data Management (MDM)  
- Digital Asset Management (DAM)  
- Enterprise Content Management (CMS/UX)  

The system is developed in PHP, follows the Model-View-Controller (MVC) architecture, and is built on the Symfony Framework.

OpenDXP manages three types of elements that cover all data types:  
Documents, Assets, and Objects.  
Following the single-source publishing principle, each element is stored once with a unique ID.  
This ID is used as a reference whenever the element is reused.

The Core Framework includes many core features that you can fully customize or extend using additional bundles.  
(See the OpenDXP Extensions section for a list of officially maintained extensions.)

## Documentation Overview

The Core Framework documentation is divided into three sections that aim to guide the reader through its first use of the platform:

* See the [Getting Started](#getting-started) section for an overview of the Core Framework or information about the installation process and the MVC pattern integration within OpenDXP.
* See the [Element Types](#element-types) section for details about managed elements in OpenDXP and associated actions.
* See the [Platform Topics](#platform-topics) section for documentation about all features implemented within OpenDXP.

### Getting Started
* [Overview](./00_Overview/README.md) 
* [Getting Started](./01_Getting_Started/README.md) 
* [MVC](./02_MVC/README.md) 

### Element Types
* [Documents - *Managing Web Pages*](./03_Documents/README.md) 
* [Assets - *Media Library / Digital Asset Management*](./04_Assets/README.md) 
* [Objects - *Custom Data Models / Entities, PIM / MDM*](./05_Objects/README.md) 

### Platform Topics
* [Multilanguage & Localization](./06_Multi_Language_i18n/README.md) 
* [Workflow Management](./07_Workflow_Management/README.md) 
* [Tools & Features](./18_Tools_and_Features/README.md) 
* [Development Tools & Details](./19_Development_Tools_and_Details/README.md) 
* [Extending & Advanced Topics](./20_Extending_OpenDxp/README.md) 
* [Deployment](./21_Deployment/README.md) 
* [Administration](./22_Administration_of_OpenDxp/README.md) 
* [Installation & Upgrade](./23_Installation_and_Upgrade/README.md)
* [Contributing](./27_Contributing/README.md)
