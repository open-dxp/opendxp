# Tests — Claude Instructions

Tests use **Pest 4** on top of `open-dxp/test-foundation`. The configuration is `phpunit.xml.dist`
in the repository root, the test setup is `tests/Pest.php`.

## Where test code lives

| What                                             | Where                                     |
|--------------------------------------------------|-------------------------------------------|
| State shared between a hook and the tests of one file | `$this`, set in `beforeEach()`        |
| Behaviour a whole directory needs                | a test case in `tests/TestCase/`          |
| A stateless helper                               | a function in `tests/Helpers/`            |
| Anything that builds and writes a model          | a Foundry factory                         |
| A set of models several tests read back by name  | a Story in `tests/Story/`                 |
| The same guarantee over many values              | a dataset in `tests/Datasets/`            |
| A comparison several tests make                  | an expectation in `lib/Test/Expectation/` |

A factory for an OpenDXP model belongs in `lib/Test/Factory/`, because every package needs it. A
factory for one of the test classes of this suite belongs in `tests/Factory/`.

## Test cases

`tests/Pest.php` binds one test case per directory. A test never names its own.

| Test case             | Directory              | Why it differs                                           |
|-----------------------|------------------------|----------------------------------------------------------|
| `TestCase`            | most of `tests/Feature`| the foundation's own, a transaction per test             |
| `SchemaTestCase`      | `Feature/Schema`       | DDL commits the transaction, so it cannot roll back      |
| `SearchTestCase`      | `Feature/Search`       | InnoDB writes a full text index only at commit           |
| `CacheTestCase`       | `Feature/Cache`        | its own cache handler per test                           |
| `HttpCacheTestCase`   | `Feature/HttpCache`    | serves requests through the cache                        |
| *(none)*              | `tests/Unit`           | needs no application                                     |

A test under `SchemaTestCase` or `SearchTestCase` leaves its data behind and has to take it back
itself, in an `afterEach()`.

## Fixtures

`tests/Fixtures/` holds what the suite installs once, before any test runs:

```
classificationstores/   a store the classes name by id, installed first
fieldcollections/       named by a class, so installed before the classes
classes/                the test classes
objectbricks/           they register themselves on the classes, so installed last
```

The order is set in the foundation's `InstallDefinitions` extension and matters.

## Running tests

Always through the MCP tool `opendxp-testkit`, never through composer or pest on the host. The
working tree has no `vendor` directory and needs none.

```
run_tests(bundle="opendxp")                              everything
run_tests(bundle="opendxp", filter="Document.Editable")   one file
run_tests(bundle="opendxp", filter="tests/Feature/Cache") one directory
```

`filter` takes a path as it is. Anything else narrows by name.

Run the narrowest thing that answers the question. Running everything is a decision, not a
precaution.

## What is frozen

`tests/Support/` is Codeception and stays untouched until OpenDXP 2.0. The documentation tells
projects to reach those classes through an autoloader line, so none of them may be removed or
renamed. They all carry `@deprecated since OpenDXP 1.5 and will be removed in 2.0`. Codeception
itself is no longer a dependency of this package, so a project that uses them declares it.

Nothing new goes into `tests/Support/`, and no other package uses the word.
