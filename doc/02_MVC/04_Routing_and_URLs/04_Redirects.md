# URLs Based on Redirects
:::caution

To use this feature, please enable the `OpenDxpSeoBundle` in your `bundle.php` file and install it accordingly with the following command:

`bin/console opendxp:bundle:install OpenDxpSeoBundle`

:::

## Introduction
Redirects are a useful feature of OpenDXP for directing the user to the correct pages - may it be for marketing URLs, 
 for redirects after a website relaunch or redirects for moved Documents. 
 
Depending on their priority, Redirects come second (priority 99) or fifth (all other priorities) in the route processing priority.  


## Setting up Redirects
Redirects are configured in the Redirects editor, accessible via the Tools menu. 

#### Regular Expression and Back-Reference Syntax

You can use regular expressions to define the sources, the placeholders in the regex can be accessed in the target 
URL using the PCRE back-reference syntax. 

![Regex and Backreference](../../img/redirects2.png)

Notice: Only simple `$1-n` references are possible, no special back-reference syntax. 


#### Priority

Each redirect has a priority.

![Redirect Priority](../../img/redirects3.png)
 
* 99 (override all): Redirects with priority 99 come second in route processing and therefore overwrite document paths and custom routes. 
* 1 (lowest) - 10 (highest): Redirects with priority 1 to 10 come fifth in route processing and are processed after document paths and custom routes. 


#### Creating Redirects When Moving or Renaming Documents
OpenDXP provides the ability to automatically create Redirects when renaming and moving Documents (in terms of SEO and user experience). 

```yaml
opendxp_seo:
    redirects:
        auto_create_redirects: true
```

A page with a pretty URL gets no redirect for its former path when it is moved, because visitors
reach it under its pretty URL.


#### Matching

A redirect compares the part of the URL its type names: the path, the path with the query, or the entire URI. An exact
source matches regardless of upper and lower case and regardless of accents, so `/Über-Uns` matches `/uber-uns`.

An exact source is tried before any regular expression. Among regular expressions, the one with the highest priority
wins. A redirect without a source site applies only to requests outside of a site.


#### Domain Redirects

A redirect of the type `domain` sends every request to a host somewhere else, whatever its path. Its source is the host
name, for example `summer2026.example.com`, and its target is a full URL or a path on a target site. With "pass
through path", the path of the request is appended to the target, which moves a whole domain:
`old-brand.com/products/shoes` goes to `new-brand.com/products/shoes`.

A domain redirect runs before a site or the system settings send an additional domain to its main domain.


#### Scheduling

A redirect can be valid from a date, until a date, or both. Outside of that time it does not apply. Two redirects can
share a source when their times differ, for example a campaign target for the summer and a permanent one for the rest
of the year. The one with the higher priority applies while both are valid.


#### Protected Redirects

A protected redirect is visible only to users with the permission "Manage protected redirects" (`redirects_protected`).
Administrators have it. Use it for redirects an agency or the SEO team owns, like the redirects of a relaunch.

Nobody without the permission can override a protected redirect:

* Within its stage, a protected redirect wins over every redirect that is not protected, whatever their priorities.
* Creating, importing or changing an exact source that a protected redirect already uses is refused. The message does
  not reveal the protected redirect.
* A protected redirect cannot be changed, deleted, exported or imported without the permission.


#### Validation

The editor refuses a redirect that cannot work: a regular expression that does not compile, a redirect whose target is
its own source, and a domain redirect whose target would stay on the same domain. It warns about a source that another
redirect already uses and about a target that redirects again, and saves the redirect anyway.

To tell search engines that a page has been removed for good, answer with the status code `410 Gone`.


#### Hit Statistics

OpenDXP counts how often each redirect answers a request, and when it did last. The count is written after the
response has been sent, so a visitor does not wait for it. It shows whether a redirect is still needed.

Counting can be switched off:

```yaml
opendxp_seo:
    redirects:
        count_hits: false
```


#### Performance

OpenDXP compiles the active redirects into a PHP file in the cache directory. OPcache keeps the file in shared memory,
so a request reads the redirects without querying them. A request costs a single lookup in the cache for the revision
of the redirects, however many redirects there are.

Saving, deleting or importing a redirect clears the cache tag `redirect`, and the next request compiles the redirects
again. Every server of a cluster compiles its own file from the shared cache revision.


#### Creating custom redirect status codes
The redirect status codes list can be extended by adding custom codes in config.yaml:

```yaml
opendxp_seo:
    redirects:
        status_codes:
            308: Permanent Redirect
```


The new status codes can be seen in admin.

![Redirect Priority](../../img/redirects7.png)
