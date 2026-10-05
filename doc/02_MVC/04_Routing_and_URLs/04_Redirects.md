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
Redirects are managed in Tools > Redirects.

You edit a redirect directly in its row. The pencil icon opens a window with all fields, including the ones the grid
does not show: the domain options, the validity, the protection and the usage. You can drag a document onto the
target field.

The columns for hits, last hit and protection are hidden by default. You can show them in the column menu of the grid.

The dropdown above the grid filters the list. It shows active, inactive, scheduled, expired or protected redirects, or
redirects without a hit in the last 90 days.

The checkbox in front of a row selects the redirect. "Selection" then activates, deactivates or deletes all selected
redirects.

#### Creating Redirects From the HTTP Error Log

The HTTP errors are listed in Marketing > SEO > HTTP Errors. For a URL that was not found (404), the redirect button
of its row opens a new redirect, with the path and the site of the URL already filled in. Once you save the redirect,
the URL disappears from the log.

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

The type of a redirect decides which part of the URL is compared: the path, the path with the query, or the entire
URI. Exact sources ignore upper and lower case and accents, so `/Über-Uns` matches `/uber-uns`.

Exact sources are checked before regular expressions. If several regular expressions match, the one with the highest
priority wins. A redirect without a source site only applies to requests outside of any site.


#### Domain Redirects

A domain redirect sends every request for a host to another address, whatever the path. The source is the host name,
for example `summer2026.example.com`. The target is a full URL or a path on a target site.

With "Pass through path", the path of the request is added to the target. This moves a whole domain:
`old-brand.com/products/shoes` goes to `new-brand.com/products/shoes`.

Domain redirects run before OpenDXP sends an additional domain to the main domain of its site.


#### Scheduling

A redirect can have a start date, an end date, or both. Outside of this time it does not apply. The end date has to be
after the start date.

Two redirects can use the same source at different times, for example a summer campaign and a permanent target. If
both are valid at the same time, the one with the higher priority wins.


#### Protected Redirects

Only users with the permission "Manage protected redirects" (`redirects_protected`) see protected redirects.
Administrators have this permission. Use it for redirects that an agency or the SEO team is responsible for, for
example after a relaunch.

Users without the permission cannot override a protected redirect:

* A protected redirect wins over redirects that are not protected, whatever their priority.
* They cannot create, import or change a redirect with the same exact source. The error message does not reveal the
  protected redirect.
* They cannot change, delete, export or import a protected redirect.


#### Validation

The editor refuses a redirect that cannot work:

* a regular expression that does not compile
* a target that is the same as the source
* a domain redirect whose target stays on the same domain
* an end date before the start date

The editor warns when another redirect already uses the same source, or when the target redirects again. It saves the
redirect anyway.

Use the status code `410 Gone` to tell search engines that a page was removed for good.


#### Hit Statistics

OpenDXP counts how often each redirect is used and when it was used last. This helps to decide whether a redirect is
still needed. The count is written after the response is sent, so visitors do not wait for it.

To switch counting off:

```yaml
opendxp_seo:
    redirects:
        count_hits: false
```


#### Performance

OpenDXP compiles all active redirects into a PHP file in the cache directory. OPcache keeps this file in memory. A
request therefore needs no database query for redirects, only one cache lookup, however many redirects exist.

Saving, deleting or importing a redirect clears the cache tag `redirect`, and the next request compiles the file
again. In a cluster, every server compiles its own file.


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
