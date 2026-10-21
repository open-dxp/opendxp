# Domain and Host Handling

## Introduction

OpenDXP needs to know the "general" (main) host of your installation for various purposes: generating
absolute URLs in CLI commands, building static page paths, resolving redirects without a site context,
and more.

For simple single-domain installations this is a static value from configuration. For multi-site
installations the general domain may need to be determined dynamically – for example from site settings
configured in the backoffice. The `GeneralHostResolver` service covers both cases without breaking
existing setups.


## Static Configuration (Simple Setups)

For simple setups, define the main domain in your OpenDXP configuration:

```yaml
# config/packages/opendxp.yaml
opendxp:
    general:
        domain: 'www.example.com'
```

An environment variable is also supported:

```yaml
opendxp:
    general:
        domain: '%env(APP_DOMAIN)%'
```

When a domain is configured here, OpenDXP sets Symfony's `router.request_context.host` parameter
accordingly. This means CLI commands (where no HTTP request is available) will use this value as the
default host for URL generation – the same behaviour as before this feature was introduced.

> **Web requests** are not affected: Symfony overrides the router host from the actual request
> automatically, so the configured value only matters in CLI context.


## Dynamic Resolution via GeneralHostResolver

The `OpenDxp\Http\Request\Host\GeneralHostResolver` service resolves the general host at runtime
using the following priority order:

1. **Registered providers** (tagged `opendxp.general_host_provider`), in descending tag priority.
   The first provider that returns a non-null, non-empty string wins.
2. **Static fallback** – the value of `opendxp.general.domain` from config (YAML / env).

### Passing Context to Providers

`resolve()` passes an optional `$context` array.

```php
use OpenDxp\Http\Request\Host\GeneralHostProviderInterface;

$host = $this->generalHostResolver->resolve([GeneralHostProviderInterface::CONTEXT_SOURCE => $request]);
```

| Key      | Constant                                       | Value                                  |
|----------|------------------------------------------------|----------------------------------------|
| `source` | `GeneralHostProviderInterface::CONTEXT_SOURCE` | The current `Request`, if there is one |

A provider that returns a host the admin UI is not served on, such as the domain of a decoupled frontend, can check the
source request with `OpenDxpContextResolver::matchesOpenDxpContext($request, OpenDxpContextResolver::CONTEXT_ADMIN)` and
return `null` there. The resolution then goes on with the next provider and finally with `opendxp.general.domain`.

## Implementing a Custom Provider

To participate in host resolution, implement `GeneralHostProviderInterface` and tag your service with
`opendxp.general_host_provider`. Use the `priority` tag attribute to control ordering (higher wins):

```php
use OpenDxp\Http\Request\Host\GeneralHostProviderInterface;

class MyGeneralHostProvider implements GeneralHostProviderInterface
{
    public function provide(array $context = []): ?string
    {
        // return null to pass through to the next provider / static config fallback
        return $this->lookUpDomainSomehow();
    }
}
```

```yaml
# config/services.yaml
App\Host\MyGeneralHostProvider:
    tags:
        - { name: opendxp.general_host_provider, priority: 10 }
```

See [Dependency Injection Tags](../../20_Extending_OpenDxp/13_Dependency_Injection_Tags.md) for a
complete list of available tags.


## Multi-Site Setups

In multi-site installations each site already has its own domain configured in the Document tree (see
[Working with Sites](./08_Working_with_Sites.md)). For the *general* domain – used when no site context
is available – you have two options:

**Option A – Static config per environment**
Set `opendxp.general.domain` in a per-environment config file or `.env`. Straightforward for setups
where one environment always maps to one main domain.

**Option B – Backoffice-driven (via a provider)**
A bundle can register a `GeneralHostProviderInterface` implementation that reads the general domain from the database.


## CLI Context

In CLI commands there is no HTTP request, so the resolved host depends entirely on what the providers
(or the static config) return. If neither is configured, Symfony's built-in default (`localhost`) is
used – the same fallback that existed before.

Commands that are sensitive to the host context (e.g. generating absolute URLs for a specific site)
should accept a `--site` option and pass the resolved site's domain explicitly rather than relying on
the general host resolver.


## Trusted Hosts

OpenDXP knows the hosts that belong to the installation:

- the general domain
- the main domain and the additional domains of every site
- the hosts in `opendxp.security.trusted_hosts`
- the hosts that a service tagged `opendxp.trusted_host_pattern_provider` adds

The admin bundle uses this list for the links in login mails. A link uses the host of the request only if the host is trusted.

Most installations need no configuration. Add a host only if it is neither the general domain nor a domain of a site,
like an admin that runs on a domain of its own:

```yaml
opendxp:
    security:
        trusted_hosts:
            - 'admin.example.com'
            - '*.example.com'
```

`*.example.com` trusts every subdomain of `example.com`. Use it only if you control every subdomain. 
A forgotten DNS entry that points to a service you no longer use lets someone else take over that subdomain.

The list can come from an environment variable:

```yaml
opendxp:
    security:
        trusted_hosts: '%env(csv:OPENDXP_TRUSTED_HOSTS)%'
```

### Reject untrusted hosts

Turn the check on to answer every request to an unknown host with 400:

```yaml
opendxp:
    security:
        reject_untrusted_hosts: true
```

OpenDXP passes the trusted hosts to `Request::setTrustedHosts()` of Symfony.

> [!NOTE]
> A project that sets `framework.trusted_hosts` or the environment variable `TRUSTED_HOSTS` checks the hosts with
> these patterns. `reject_untrusted_hosts` then has no effect.

> [!CAUTION]
> A health check that calls the server by its IP address needs the IP address in `opendxp.security.trusted_hosts`.

### Add trusted hosts in a bundle

A bundle adds hosts with a service that implements `TrustedHostPatternProviderInterface` and carries the tag
`opendxp.trusted_host_pattern_provider`. It gets the patterns of the known hosts and returns more patterns:

```php
use OpenDxp\Http\Request\Host\TrustedHostPatternProviderInterface;

final class BackofficeHostPatternProvider implements TrustedHostPatternProviderInterface
{
    public function getPatterns(array $knownHostPatterns): array
    {
        // ^shop\.com$ becomes ^backoffice\.shop\.com$
        return array_map(
            static fn (string $pattern): string => '^backoffice\\.' . substr($pattern, 1),
            $knownHostPatterns,
        );
    }
}
```
