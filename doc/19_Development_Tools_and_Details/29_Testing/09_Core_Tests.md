# Core Tests

This page describes the test suite of OpenDXP itself. It works like the suite of any other package.
This page shows only where it differs.

## Redis

The cache tests run against two cache pools, Doctrine DBAL and Redis. The Redis case skips itself
unless `OPENDXP_TEST_REDIS_DSN` names a Redis server. CI always runs it.
[External services](https://github.com/open-dxp/test-foundation#external-services) shows how you get
a Redis server for your run.

## Directory structure

The suite follows [the layout of a suite](./03_Writing_Tests.md#the-layout-of-a-suite), with these
differences:

```
tests/
    Application/    also an areabrick with its brick, and the bundle the installer tests install
    PHPUnit/        the PHPUnit extension CreateMissingTables
    Support/        the Codeception helpers of OpenDXP 1.x
```

The suite has no datasets for all of its tests. Each of its datasets belongs to one directory, in
`Feature/Cache/Datasets.php`, `Feature/Dependency/Datasets.php` and `Feature/Element/Datasets.php`.

The factories for the models of OpenDXP live in `lib/Test/Factory/`, and the expectations in
`lib/Test/Expectation/`. Every package uses them, so they belong to the [Test API](./07_Test_API.md)
and not to this suite.

## Bundles in the test kernel

The test kernel in `tests/Application/TestKernel.php` adds the bundles that ship with OpenDXP and
that the tests cover: the application logger, custom reports, generic execution engine, glossary,
SEO, simple backend search and static routes bundles. In the environment `http_cache` it also adds
`FOSHttpCacheBundle`.

## Test cases

`tests/Pest.php` registers the expectations and assigns one test case per directory. Unit tests get
no test case.

| Test case                        | Directory                        | Why it differs                                                                                                        |
|----------------------------------|----------------------------------|-----------------------------------------------------------------------------------------------------------------------|
| `TestCase`                       | most of `Feature`                | The test case of the foundation.                                                                                      |
| `CacheTestCase`                  | `Feature/Cache`                  | Each test gets a cache pool and a cache handler of its own.                                                           |
| `GenericExecutionEngineTestCase` | `Feature/GenericExecutionEngine` | The tests handle job messages like a worker, in the environment `generic_execution_engine`.                           |
| `HttpCacheTestCase`              | `Feature/HttpCache`              | Requests are served through the HTTP cache, in the environment `http_cache`.                                          |
| `InstallerTestCase`              | `Feature/Installer`              | The tests install a bundle in the environment `installer`. Its migrations change the schema, so there is no rollback. |
| `SchemaTestCase`                 | `Feature/Schema`                 | DDL commits the transaction, so there is no rollback.                                                                 |
| `SearchTestCase`                 | `Feature/Search`                 | InnoDB writes a full text index only on commit, so there is no rollback.                                              |

The schema tests remove what they wrote in `afterEach()`. `SearchTestCase` and `InstallerTestCase`
do it in their `tearDown()`.

## Missing tables

`phpunit.xml.dist` registers one more extension than other packages.
`tests/PHPUnit/CreateMissingTables.php` creates the table `versionsData`. The version storage writes
to it, but nothing in core creates it. The table gets the collation of `versions`, because queries
join the two tables.

## Installer tests

`Feature/Installer` tests the installer API that core offers to bundles. An installer marks the
migrations of its bundle as executed or not executed, and it brings the entity tables of its bundle
to their mapping. The tests install the bundle in `tests/Application/InstallerBundle`, so they change
the schema of the database. `InstallerTestCase` drops its tables, its migration versions and its
installed marker after every test.

## Codeception helpers

`tests/Support/` holds the Codeception helpers of OpenDXP 1.x. They are deprecated and stay until
2.0, because projects still run their Codeception tests through them. Nothing new goes there, and
nothing new refers to them.
