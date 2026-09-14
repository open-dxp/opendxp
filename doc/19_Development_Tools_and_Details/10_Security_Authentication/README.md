# Security and Authentication

You can make full use of the [Symfony Security Component](https://symfony.com/doc/current/security.html) to handle complex
authentication/authorization scenarios. 
Please be aware that also the OpenDXP admin UI uses the Security component, so be careful 
when changing/modifying the configuration. 

## Login example

A simplified guide to this setup is illustrated in [Authenticate against OpenDXP Objects](./01_Authenticate_OpenDxp_Objects.md).

For more complex examples, custom user providers and a full configuration reference please read the
[Symfony Security Component documentation](https://symfony.com/doc/current/security.html).

To use `#[IsGranted]`/`Security::isGranted()` against OpenDXP's own permission checks, see [Permission Voters](./10_Permission_Voters.md).

If you register a custom authenticator or store your own objects via `TmpStore`, see
[Restrict Deserialization Classes](./06_Restrict_Deserialization_Classes.md) to make sure they
still deserialize correctly.
