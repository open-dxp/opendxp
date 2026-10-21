# Dependency Injection Tags

Please read the intro of [dependency injection tags](https://symfony.com/doc/current/reference/dic_tags.html) of Symfony first.
 
In addition to the tags provided by Symfony, OpenDXP adds it's own tags to flag special purpose services. 

Following an overview of all additional service tags provided by OpenDXP: 
 
| Name                                    | Usage                                                                                                                                                             |
|-----------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `opendxp.area.brick`                    | Used to register your [custom area bricks](../03_Documents/01_Editables/02_Areablock/02_Bricks.md), which are not loaded by the discovering service               |
| `opendxp.general_host_provider`         | Register a provider for the general (main) host of the installation. See [Domain and Host Handling](../02_MVC/04_Routing_and_URLs/06_Domain_and_Host_Handling.md) |
| `opendxp.trusted_host_pattern_provider` | Add patterns of trusted hosts. See [Domain and Host Handling](../02_MVC/04_Routing_and_URLs/06_Domain_and_Host_Handling.md#add-trusted-hosts-in-a-bundle)         |
