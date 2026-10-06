# Core Tests

This page describes the test suite of OpenDXP itself. It covers documents, assets, data objects, the
cache, permissions, search and more. The suite works like the suite of any other package, as the
rest of this chapter describes. This page shows only what it adds.

## Run the suite

From your checkout of `open-dxp/opendxp`:

```bash
testkit test
testkit test tests/Feature/Cache
testkit test --filter="reads a relation back"
```

The first run builds the application and takes a few minutes. After that, a single file takes a few
seconds. Run the narrowest thing that answers your question.

The cache tests run against two cache pools, Doctrine DBAL and Redis. The Redis case skips itself
unless `OPENDXP_TEST_REDIS_DSN` names a Redis server. CI always runs it. To run it on your machine,
add Redis to the services of the testkit in its `.ddev/testkit.yaml`:

```yaml
services: [redis]
```

Run `ddev restart` in the testkit afterwards. The testkit then sets `OPENDXP_TEST_REDIS_DSN` for
every run.

## Where things live

```
tests/
    Pest.php        registers the expectations and assigns the test cases
    Feature/        tests that boot the application, one directory per subject
    Unit/           tests that boot nothing
    Application/    the test application: kernel, configuration, templates, controllers, services,
                    an areabrick with its brick, and the bundle the installer tests install
    Factory/        factories for the classes the suite installs
    Story/          fixed worlds with fixed values
    TestCase/       the test cases this suite adds
    Helpers/        functions Pest loads, no classes
    Value/          value classes that helpers and test cases return instead of arrays
    Fixtures/       class definitions, bundles and files the tests use
    PHPUnit/        the PHPUnit extension CreateMissingTables
    Support/        the Codeception helpers of OpenDXP 1.x
```

The suite has no datasets for all of its tests. Each of its datasets belongs to one directory, in
`Feature/Cache/Datasets.php` and `Feature/Element/Datasets.php`.

The factories for the models of OpenDXP live in `lib/Test/Factory/`, and the expectations in
`lib/Test/Expectation/`. Every package uses them, so they belong to the [Test API](./07_Test_API.md)
and not to this suite.

The test kernel in `tests/Application/TestKernel.php` adds the bundles that ship with OpenDXP and
that the tests cover: the glossary, SEO, simple backend search and static routes bundles. In the
environment `http_cache` it also adds `FOSHttpCacheBundle`.

## Test cases

`tests/Pest.php` registers the expectations and assigns one test case per directory. Unit tests get
no test case.

| Test case           | Directory           | Why it differs                                                                                                        |
|---------------------|---------------------|-----------------------------------------------------------------------------------------------------------------------|
| `TestCase`          | most of `Feature`   | The test case of the foundation. Every test runs in a transaction that is rolled back.                                |
| `CacheTestCase`     | `Feature/Cache`     | Each test gets a cache pool and a cache handler of its own.                                                           |
| `HttpCacheTestCase` | `Feature/HttpCache` | Requests are served through the HTTP cache, in the environment `http_cache`.                                          |
| `InstallerTestCase` | `Feature/Installer` | The tests install a bundle in the environment `installer`. Its migrations change the schema, so there is no rollback. |
| `SchemaTestCase`    | `Feature/Schema`    | DDL commits the transaction, so there is no rollback.                                                                 |
| `SearchTestCase`    | `Feature/Search`    | InnoDB writes a full text index only on commit, so there is no rollback.                                              |

A test case without the rollback carries `#[SkipDatabaseRollback]`, and its tests remove what they
wrote. The schema tests do this in `afterEach()`. `SearchTestCase` and `InstallerTestCase` do it in
their `tearDown()`.

`HttpCacheTestCase` and `InstallerTestCase` extend `EnvironmentTestCase`. Their configuration lives
in `tests/Application/config/http_cache.yaml` and `tests/Application/config/installer.yaml`. Both
files import `test.yaml`.

## Before the first test

`phpunit.xml.dist` names four extensions. Three of them are the ones every package uses: the
rollback of DAMA, Foundry, and `InstallDefinitions` of the test foundation.

`InstallDefinitions` installs the class definitions of the suite once, before the first test. They
live in `tests/Fixtures/`: classification stores, field collections, classes and object bricks,
installed in this order. Classes refer to classification stores and field collections, and object
bricks refer to classes. Each kind is installed after the kinds it refers to.

The fourth extension belongs to this suite. `tests/PHPUnit/CreateMissingTables.php` creates the
table `versionsData`, which the version storage writes to and which nothing in core creates. It
gives the table the collation of `versions`, because the two tables are joined.

## Installer tests

`Feature/Installer` tests the installer API that core offers to bundles. An installer marks the
migrations of its bundle as executed or not executed, and it brings the entity tables of its bundle
to their mapping. The tests install the bundle in
`tests/Application/InstallerBundle`, so they change the schema of the database. `InstallerTestCase`
drops its tables, its migration versions and its installed marker after every test.

## Static analysis

`testkit analyse` runs the linters and PHPStan against the same application. The options are
described in [Testing](./README.md#run-the-tests).

## Contributing tests

Tests are very welcome, both new ones and better ones. Name a test with a sentence that says what is
guaranteed, put it into the directory of its subject, and create its data with a factory.
[Writing Tests](./03_Writing_Tests.md) shows the form the suite follows.

`tests/Support/` holds the Codeception helpers of OpenDXP 1.x. They are deprecated and stay until
2.0, because projects still run their Codeception tests through them. Nothing new goes there, and
nothing new refers to them.
