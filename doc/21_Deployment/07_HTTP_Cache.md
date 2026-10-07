# HTTP Cache

OpenDXP implements **tag-based HTTP cache invalidation** on top of [FOSHttpCacheBundle](https://foshttpcachebundle.readthedocs.io/en/stable/).
FOSHttpCacheBundle writes a cache tags header to every cacheable response, listing all content dependencies. 
When content changes, only the affected cached responses are invalidated, no full cache flush required.

The response header name (`X-Cache-Tags` by default) is determined by the configured FOSHttpCacheBundle proxy client. 
Varnish `purgekeys` mode uses `xkey`; Fastly uses `Surrogate-Key`. See the [FOSHttpCacheBundle proxy client docs](https://foshttpcachebundle.readthedocs.io/en/stable/reference/configuration/proxy-clients.html) for details.

***

## Overview

- [How it works](#how-it-works)
- [Setup](#setup)
- [Configuration reference](#configuration-reference)
- [Project-specific tags](#project-specific-tags)
- [Skipping invalidation](#skipping-invalidation)
- [Filtering tag collection](#filtering-tag-collection)
- [Disabling tag collection](#disabling-tag-collection)
- [ESI fragments](#esi-fragments)
- [Symfony Web Profiler](#symfony-web-profiler)
- [Proxy configuration](#proxy-configuration)
- [Symfony HttpCache Example](#symfony-httpcache-example)

***

## How it works

### 1. Tag collection during request

When a page is rendered, OpenDXP automatically collects cache tags for every element that was loaded. Tags are added via registered **HttpCacheTagStrategyInterface** services.

Built-in tags collected automatically:

| Source                                 | Tags added                                                  |
|----------------------------------------|-------------------------------------------------------------|
| Route document (e.g. `/about-us`)      | `document_42`                                               |
| Route DataObject (URL slug)            | `data_object_17`, `data_object_class_employee` <sup>*</sup> |
| Document loaded during rendering       | `document_5`, `document_12`                                 |
| DataObject loaded during rendering     | `data_object_3`, `data_object_8`                            |
| Asset loaded during rendering          | `asset_22`                                                  |
| Document listing executed              | `document_list` <sup>*</sup>                                |
| Asset listing executed                 | `asset_list` <sup>*</sup>                                   |
| DataObject listing executed            | `data_object_class_employee` <sup>*</sup>                   |
| Translation changed <sup>**</sup>      | `translation`                                               |
| `WebsiteSetting::getById()` called     | `website_setting_5`                                         |
| `opendxp_website_config('key')` called | `website_setting_5`                                         |
| `opendxp_website_config()` called      | `website_setting_list`                                      |

\* Only when `tag_list: true` is set (default) in the `elements` config.  
\** Translations in the `admin` domain are never tagged or invalidated, since they are not part of a cached frontend response.

> [!NOTE]
> Tags are only collected during cacheable HTTP methods (GET, HEAD). 
> POST/PUT/DELETE requests never contribute to the response tag set.

> [!IMPORTANT] 
> By default (`scope: controller`) collection starts at `kernel.controller`: elements loaded before
> rendering (e.g. in `kernel.request` listeners) are not tagged. Set `scope: request` to collect
> from the very beginning of the request.

### 2. Fallback document tagging

Every route other than a document route (e.g. static route, symfony route) has no document of its own.
For these, OpenDXP still resolves a "fallback" document, the nearest document found by path.

By default, this fallback document is also tagged and invalidated like any other content document. 
If your route's response does not actually depend on that fallback document,
set `tag_fallback_document: false` to stop tagging/invalidating it for routes that only resolved a fallback. 

### 3. Tags written to response header

FOSHttpCacheBundle's `TagListener` writes all collected tags to the response header configured for the active proxy client. For example, with Varnish `ban` mode:

```
X-Cache-Tags: document_42 data_object_17 data_object_class_employee asset_22
```

The reverse proxy stores this header alongside the cached response and strips it before delivering to the browser.

### 4. Invalidation on content change

When a Document, DataObject, Asset, Translation or WebsiteSetting is saved or deleted, `ElementChangeListener` calls `HttpCache::invalidate()` directly. FOSHttpCacheBundle's `InvalidationListener` flushes all queued invalidation requests to the proxy after the response is sent (`kernel.terminate`).

A messenger worker sends the invalidations after each message it handles. A page that a scheduled task publishes, or data that an import saves in a worker, reaches the proxy right away and not only when the worker stops.

```
Save Document 42
  → ElementChangeListener::onDocumentChange()
  → HttpCache::invalidate($document)
  → OpenDxpElementCacheStrategy::getTags() → ['document_42', 'document_list']
  → CacheManager::invalidateTags(['document_42', 'document_list'])
  → kernel.terminate → FOSHttpCacheBundle flushes to proxy
  → Varnish/Fastly/etc. invalidates all responses tagged document_42 or document_list
```

***

## Setup

### 1. Install and register FOSHttpCacheBundle

```bash
composer require friendsofsymfony/http-cache-bundle
```

```php
// config/bundles.php
FOS\HttpCacheBundle\FOSHttpCacheBundle::class => ['all' => true],
```

If `opendxp.http_cache.enabled: true` but the bundle is missing or not registered, OpenDXP throws a `LogicException` at container compile time with install instructions.

### 2. Enable in OpenDXP

```yaml
# config/packages/opendxp.yaml
opendxp:
    http_cache:
        enabled: true
```

### 3. Configure FOSHttpCacheBundle

OpenDXP integrates with FOSHttpCacheBundle's proxy client infrastructure. Configure the proxy client for your environment:

```yaml
# config/packages/fos_http_cache.yaml
fos_http_cache:
    proxy_client:
        varnish:
            tags_header: My-Cache-Tags
            tag_mode: ban
            header_length: 1234
            http:
                servers:
                    - 123.123.123.1:6060
                    - 123.123.123.2
                base_url: yourwebsite.com
    tags:
        enabled: true
```

**Supported proxy clients:** Varnish, Fastly, nginx, Cloudflare, Symfony HttpCache. 
See [FOSHttpCacheBundle proxy client docs](https://foshttpcachebundle.readthedocs.io/en/stable/reference/configuration/proxy-clients.html).

***

### 4. Configure Cache-Control headers

OpenDXP does not set `Cache-Control` headers automatically.
Use FOSHttpCacheBundle's `cache_control` rules to define which responses should be cached and for how long.
The following example globally enables caching for all non-admin GET/HEAD requests.

This could be be a good starting point for most projects:

```yaml
# config/packages/fos_http_cache.yaml
fos_http_cache:
    cache_control:
        defaults:
            overwrite: false
        rules:
            -
                match:
                    path: ^(?!/(admin))
                headers:
                    cache_control:
                        public: true
                        max_age: 15
                        s_maxage: 30
                    etag: true
```

See [FOSHttpCacheBundle caching headers docs](https://foshttpcachebundle.readthedocs.io/en/latest/features/headers.html).

***

## Configuration reference

```yaml
opendxp:
    http_cache:
        enabled: true

        scope: controller           # "controller" (default) or "request": when collection starts

        tag_fallback_document: true # tag/invalidate the fallback document on routes without their own (default: true)

        elements:
            documents:
                enabled: true       # tag/invalidate documents (default: true)
                tag_list: true      # also tag/invalidate "document_list" (default: true)
            data_objects:
                enabled: true       # tag/invalidate data objects (default: true)
                tag_list: true      # also tag/invalidate "data_object_class_{className}" (default: true)
            assets:
                enabled: true       # tag/invalidate assets (default: true)
                tag_list: true      # also tag/invalidate "asset_list" (default: true)
            translations:
                enabled: true       # invalidate on translation changes (default: true)
            website_settings:
                enabled: true       # tag/invalidate website settings (default: true)
```

All `elements` options default to `true`. The full configuration above is equivalent to just setting `enabled: true`.

Disabling an element type affects **both** tag collection (response header) and invalidation (purge on save). 

Example (disable asset tracking entirely):

```yaml
opendxp:
    http_cache:
        enabled: true
        elements:
            assets:
                enabled: false
```

***

## Project-specific tags

### 1. Via FOSHttpCacheBundle (controller / route / Twig)

Tag a controller action:

```php
use FOS\HttpCacheBundle\Configuration\Tag;

#[Tag('news')]
#[Tag(expression: '"news-" ~ id')]
public function articleAction(int $id): Response { ... }
```

Tag from a Twig template:

```twig
{{ fos_httpcache_tag('news') }}
{{ fos_httpcache_tag(['news', 'news-' ~ article.id]) }}
```

Via YAML rules (no code changes needed):

```yaml
fos_http_cache:
    tags:
        rules:
            - { match: { path: ^/news }, tags: [news] }
```

See [FOSHttpCacheBundle tagging docs](https://foshttpcachebundle.readthedocs.io/en/stable/features/tagging.html) for the full reference.

***

### 2. Via Doctrine entity strategy
For Doctrine entities that need a single `{prefix}_{id}` tag, use the `DoctrineGeneralEntityCacheStrategy` service. 

Collection (on `postLoad`) and invalidation (on `postPersist`/`postUpdate`/`postRemove`) are handled automatically, 
which only fires for the registered entity classes.

```yaml
# config/services/http_cache.yaml
app.http_cache.my_entity:
    class: OpenDxp\HttpCache\DoctrineGeneralEntityCacheStrategy
    tags:
        - name: opendxp.http_cache.doctrine_entity
          entity_class: App\Entity\MyEntity
          tag_prefix: app_my_entity
          # identifier_expression: 'object.getId()'   # default / optional
```

This produces tags like `app_my_entity_42`.

> [!NOTE]
> `identifier_expression` is a Symfony expression evaluated with `object` as the entity instance.

***

### 3. Via custom strategy
Use this if you need more control about tagging or custom (special) entities.

Implement `HttpCacheTagStrategyInterface` directly and register a `HttpCacheTagStrategyInterface` service 
to cover both collection and invalidation for a custom element type with a single class:

```php
use OpenDxp\HttpCache\HttpCacheTagStrategyInterface;
use OpenDxp\HttpCache\Tag\CacheTag;

class BlogPostCacheStrategy implements HttpCacheTagStrategyInterface
{
    public function supports(object $element): bool
    {
        return $element instanceof BlogPost;
    }

    public function getTags(object $element): array
    {
        return [
            new CacheTag(BlogTagType::Post, $element->getId()),
            new CacheTag(BlogTagType::PostList),
        ];
    }
}
```

Define your tag types with `CacheTagType`:

```php
enum BlogTagType: string implements CacheTagType
{
    case Post     = 'blog';
    case PostList = 'blog_list';

    public function prefix(): string { return $this->value; }
}
```

Register the strategy with the DI tag:

```yaml
# config/services.yaml
App\Cache\BlogPostCacheStrategy:
    tags:
        - { name: opendxp.http_cache.strategy }
```

**Collection**: call when a BlogPost is loaded:

```php
// In a postLoad listener, or repository, or controller:
$this->invalidator->collectTagsFor($blogPost);
```

**Invalidation**: call when a BlogPost changes:

```php
// In a postUpdate/postDelete listener or wherever mutations happen:
$this->invalidator->invalidate($blogPost);
```

Inject `HttpCache` from the container:

```php
use OpenDxp\HttpCache\HttpCache;

class BlogPostRepository
{
    public function __construct(private readonly HttpCache $httpCache) {}
}
```

***

## Skipping invalidation

To skip cache invalidation for a specific save operation, pass `HttpCacheArguments::SKIP_INVALIDATION`:

```php
use OpenDxp\HttpCache\HttpCacheArguments;

// Publish silently without purging the cache
$document->save([HttpCacheArguments::SKIP_INVALIDATION => true]);
```

`saveVersionOnly` and `autoSave` operations are automatically excluded from invalidation.

***

## Filtering tag collection

Subscribe to `HttpCacheEvents::TAG_GUARD` to conditionally prevent tags from being tracked for specific elements. 
This event fires **before** tags enter the response collector. Cancelling it prevents the tags from ever being written to the header.

```php
use OpenDxp\Event\HttpCache\HttpCacheTagGuardEvent;
use OpenDxp\Event\HttpCacheEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: HttpCacheEvents::TAG_GUARD)]
class InternalDocumentTagGuard
{
    public function __invoke(HttpCacheTagGuardEvent $event): void
    {
        if ($event->element instanceof Document && $event->element->getProperty('internal')) {
            $event->cancel(); // tags are never added to the collector
        }
    }
}
```

To add or modify tags, register a `HttpCacheTagStrategyInterface` strategy instead: the guard is a filter only.

***

## Disabling tag collection

### Entire request

Inject `HttpCacheScope` and call `disable()` to prevent tag collection for the entire request.

```php
use OpenDxp\HttpCache\HttpCacheScope;

public function __construct(private readonly HttpCacheScope $httpCacheScope) {}

public function accountAction(): Response
{
    $this->httpCacheScope->disable(); // no tags collected for this request
    // ...
}
```

### Specific code block (e.g. inside an areabrick)

Use `suspended()` to temporarily suppress tag collection for a specific block of code.
Collection resumes automatically after the callable returns.

```php
// Elements loaded inside the closure are not tagged.
// Other elements loaded outside it are tagged normally.
$this->httpCacheScope->suspended(function () {
    $this->relatedItems->load();
});
```

### Force collection
By default, collection only starts at `kernel.controller` (`scope: controller`). 

Two options for collecting tags earlier:

**Option 1: `scope: request`**: Set in the config to enable collection for the entire request globally.
**Option 2: `collecting(callable)`**: Force-enable collection for a specific block only, without changing the global scope.

It force-enables collection for the duration of the callable, regardless of the current scope state, and restores the previous state afterwards.

```php
use OpenDxp\HttpCache\HttpCacheScope;
use OpenDxp\HttpCache\HttpCache;

public function onKernelRequest(RequestEvent $event): void
{
    $site = $this->siteResolver->resolve($event->getRequest());
    if ($site !== null) {
        $this->httpCacheScope->collecting(function () use ($site) {
            $this->httpCache->collectTagsFor($site);
        });
    }
}
```

***

## ESI fragments
For pages mixing public and user-specific content, use Symfony's ESI support. 
Each ESI fragment is a separate HTTP request, it gets its own independent cache tags header and can be cached and invalidated separately.

```twig
{# Main page is publicly cached. Cart badge is fetched per-request. #}
{{ render_esi(controller('App\\Controller\\Cart::badgeAction')) }}
```

```php
#[Cache(smaxage: 0)]
public function badgeAction(): Response
{
    $response = $this->render('cart/badge.html.twig');
    $response->setPrivate();
    
    return $response;
}
```

***

## Symfony Web Profiler
When the Symfony profiler is activ, an **HTTP Cache Tags** panel is available in the toolbar.
It shows all tags collected for the current request, grouped by type.

***

## Proxy configuration
OpenDXP delegates all proxy communication to FOSHttpCacheBundle. 
Supported backends: Varnish (BAN and xkey/purgekeys), Fastly, nginx, Cloudflare, Symfony HttpCache.

- [FOSHttpCacheBundle proxy client configuration](https://foshttpcachebundle.readthedocs.io/en/stable/reference/configuration/proxy-client.html)
- [FOSHttpCache Varnish VCL examples](https://foshttpcache.readthedocs.io/en/latest/varnish-configuration.html)

***

## Symfony HttpCache Example
Symfony ships with a built-in reverse proxy (`HttpCache`) that runs in-process. 
This an example of how to use it with OpenDXP.

### 1. Install the tag-aware store
The default Symfony store does not support tag-based invalidation. 
Install the PSR-6 store from toflar:

```bash
composer require toflar/psr6-symfony-http-cache-store
```

### 2. Create `src/AppCache.php`

```php
namespace App;

use FOS\HttpCache\SymfonyCache\CacheInvalidation;
use FOS\HttpCache\SymfonyCache\CleanupCacheTagsListener;
use FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache;
use FOS\HttpCache\SymfonyCache\PurgeListener;
use FOS\HttpCache\SymfonyCache\PurgeTagsListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpCache\SurrogateInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Toflar\Psr6HttpCacheStore\Psr6Store;

class AppCache extends HttpCache implements CacheInvalidation
{
    use EventDispatchingHttpCache;

    public function __construct(
        HttpKernelInterface $kernel,
        ?SurrogateInterface $surrogate = null,
        array $options = [],
    ) {
        $store = new Psr6Store([
            'cache_directory'   => $kernel->getCacheDir() . '/http_cache',
            'cache_tags_header' => PurgeTagsListener::DEFAULT_TAGS_HEADER,
        ]);

        parent::__construct($kernel, $store, $surrogate, array_merge(['debug' => true], $options));

        $this->addSubscriber(new PurgeListener());
        $this->addSubscriber(new PurgeTagsListener());
        $this->addSubscriber(new CleanupCacheTagsListener());
    }

    public function fetch(Request $request, bool $catch = false): Response
    {
        return parent::fetch($request, $catch);
    }
}
```

### 3. Update `src/Kernel.php`

The kernel must implement `HttpCacheProvider` so FOSHttpCacheBundle can dispatch tag invalidation requests directly to `AppCache` without HTTP.

```php
namespace App;

use FOS\HttpCache\SymfonyCache\HttpCacheAware;
use FOS\HttpCache\SymfonyCache\HttpCacheProvider;
use OpenDxp\Kernel as OpenDxpKernel;

class Kernel extends OpenDxpKernel implements HttpCacheProvider
{
    use HttpCacheAware;

    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);
        
        $this->setHttpCache(new AppCache($this));
    }
}
```

### 4. Update `public/index.php`

Return the `AppCache` kernel instead of the inner kernel when it is available:

```php
return static function () {
    Bootstrap::bootstrap();

    $kernel = Bootstrap::kernel();

    if ($kernel instanceof \FOS\HttpCache\SymfonyCache\HttpCacheProvider) {
        return $kernel->getHttpCache();
    }

    return $kernel;
};
```

### 5. Configure FOSHttpCacheBundle

```yaml
# config/packages/fos_http_cache.yaml
fos_http_cache:
    proxy_client:
        symfony:
            use_kernel_dispatcher: true
    tags:
        enabled: true
```

`use_kernel_dispatcher: true` routes invalidation calls directly to `AppCache` in-process. 
No HTTP server setup is needed.
