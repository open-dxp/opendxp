# Core Tests

The core suite has over a thousand tests and covers documents, assets, data objects, the cache,
permissions, search and more. It runs like the tests of any other package.

## Running the suite

From your checkout of `open-dxp/opendxp`:

```bash
testkit test
testkit test tests/Feature/Cache
testkit test --filter="reads a relation back"
```

The first run builds the application and takes about two minutes. After that, a single file takes a
few seconds. Run the narrowest thing that answers your question.

## Where things live

```
tests/
    Feature/        tests that boot the application, one directory per subject
    Unit/           tests that do not boot it
    TestCase/       the test cases this suite adds
    Factory/        factories for the test classes of this suite
    Story/          sets of models several tests share
    Datasets/       Pest datasets
    Helpers/        helper functions
    Fixtures/       class definitions and files the suite installs
```

Factories for OpenDXP's own models live in `lib/Test/Factory/`, namespace `OpenDxp\Test\Factory`,
because every package uses them. Shared expectations live in `lib/Test/Expectation/`.

## Test cases

`tests/Pest.php` assigns one test case per directory.

| Test case | Directory | Why it differs |
|---|---|---|
| `TestCase` | most of `Feature` | Every test runs in a transaction that is rolled back. |
| `SchemaTestCase` | `Feature/Schema` | DDL commits the transaction, so it cannot be rolled back. |
| `SearchTestCase` | `Feature/Search` | InnoDB writes a full text index only on commit. |
| `CacheTestCase` | `Feature/Cache` | Each test gets a cache handler of its own. |
| `HttpCacheTestCase` | `Feature/HttpCache` | Requests are served through the HTTP cache. |

A test under `SchemaTestCase` or `SearchTestCase` cleans up its data itself, in an `afterEach()`.

## Fixtures

The suite installs its class definitions once, before the first test. They live in
`tests/Fixtures/`: classification stores, field collections, classes and object bricks, installed in
this order. A class names its field collections and stores, and a brick registers itself on its
classes, so the order matters.

## Static analysis

`testkit analyse` runs the linters and PHPStan against the same application. The options are
described in [Testing](./README.md#running-the-tests).

## Contributing tests

Tests are very welcome, both new ones and better ones. Name a test with a sentence that says what is
guaranteed, put it into the directory of its subject, and create its data with a factory.

`tests/Support/` holds the Codeception helpers of OpenDXP 1.x. They stay until 2.0 for projects that
still use them, and nothing new goes there.
