# Testing

This chapter shows you how to test an OpenDXP project or bundle. You write the tests with Pest,
create their data with factories, and run them in an application the testkit builds for you. Your
database and your checkout stay untouched.

Core, every bundle and every project use the same setup. Once you know it, you can test anything in
the OpenDXP world.

## The pieces

| Piece                                                                   | What it gives you                                                                                            |
|-------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------|
| [Pest](https://pestphp.com)                                             | The test framework. A test is a function with a sentence as its name.                                        |
| [Foundry](https://github.com/zenstruck/foundry)                         | The factories that create test data.                                                                         |
| [open-dxp/test-foundation](https://github.com/open-dxp/test-foundation) | The test kernel, the test cases, the browser, and the commands that build the application your tests run in. |
| `OpenDxp\Test` in `open-dxp/opendxp`                                    | The test API of OpenDXP: factories for its models, Pest expectations and installers for class definitions.   |
| [OpenDXP Testkit](https://github.com/open-dxp/docker-testkit)           | Runs your tests and static checks on your machine.                                                           |

The classes of the test API ship with OpenDXP itself. They need Pest and Foundry, which
`open-dxp/test-foundation` brings in.

## A first test

```php
use OpenDxp\Model\Document;
use OpenDxp\Test\Factory\DocumentPageFactory;

it('finds a page by its path', function () {
    $page = DocumentPageFactory::createOne(['key' => 'about-us']);

    $found = Document::getByPath('/about-us');

    expect($found?->getId())->toBe($page->getId());
});
```

The test runs against a real OpenDXP installation with a real database. The factory creates the
page and saves it. Everything the test writes is rolled back when it ends, so the next test starts
clean.

The name of the test is a sentence that says what OpenDXP guarantees. The body has three blocks:
it prepares, it acts, and it checks. [Writing Tests](./03_Writing_Tests.md) explains this form.

## Set up your package

The [README of the test foundation](https://github.com/open-dxp/test-foundation) takes a bundle or
a project from no tests to its first green test. It shows the `composer.json` entries, the
`phpunit.xml.dist`, the test kernel and `tests/Pest.php`.

Two parts of that setup belong to the test API of OpenDXP. The test API does not work without them.

The first is the Foundry extension in `phpunit.xml.dist`. It starts Foundry for every test, and
every factory depends on it:

```xml
<extensions>
    <bootstrap class="DAMA\DoctrineTestBundle\PHPUnit\PHPUnitExtension"/>
    <bootstrap class="Zenstruck\Foundry\PHPUnit\FoundryExtension"/>
    <bootstrap class="OpenDxp\TestFoundation\PHPUnit\InstallDefinitions"/>
</extensions>
```

The second is the registration of the expectations in `tests/Pest.php`. Pest knows an expectation
only after it is registered:

```php
use OpenDxp\Test\Expectation\Fields;
use OpenDxp\Test\Expectation\Redirects;
use OpenDxp\TestFoundation\TestCase;

Fields::register();
Redirects::register();

pest()->extend(TestCase::class)->in('Feature');
```

`Redirects` needs `OpenDxpSeoBundle` in your application. Leave it out if you do not use redirects.

## Run the tests

A test needs an installed OpenDXP with a database, and a bundle does not have one. A project has
one, but you do not want a test run to install, migrate or empty it. The testkit takes care of both.

The testkit is a [DDEV](https://ddev.com) project of its own. The first run of a package builds a
slot for it. A slot is a complete OpenDXP application around your package, installed into a
database of its own. Every later run copies your checkout into the slot and runs Pest there. The
slot is built again when your `composer.json` changes.

Building a slot takes a few minutes. Every run after that takes seconds. Nothing is written into
your checkout, and your repository needs no `vendor` directory. By default, five runs work side by
side, each with its own PHP version and database.

Clone the [testkit](https://github.com/open-dxp/docker-testkit) and start it once:

```bash
git clone https://github.com/open-dxp/docker-testkit
cd docker-testkit
ddev start
```

Link it into your PATH, so that you can call it from anywhere inside your checkout:

```bash
ln -s /path/to/docker-testkit/testkit ~/.local/bin/testkit
```

Then run your tests:

```bash
testkit test
testkit test tests/Feature/Document
testkit test --filter="finds a page"
testkit test --php 8.3 --db mariadb
testkit test --fresh
```

Pest options and test paths go straight to Pest. `--fresh` builds the slot again from nothing. Use
it when a dependency was released and you want the new version.

The static checks run against the same application:

```bash
testkit analyse
testkit analyse lint
testkit analyse phpstan
testkit analyse deptrac
testkit analyse phparkitect
testkit analyse phpstan --baseline
```

`lint` checks the container, the YAML configuration and the Twig templates, and it always runs.
PHPStan, Deptrac and PHPArkitect run when your package has a `phpstan.neon`, a `deptrac.yaml` or a
`phparkitect.php`. `--baseline` writes the PHPStan baseline back into your checkout.

`testkit status` lists the slots. `testkit release` frees the slot of your checkout.

> [!NOTE]
> The testkit starts no services by default. A suite that needs Redis or OpenSearch lists them
> under `services` in `.ddev/testkit.yaml` of the testkit. The testkit then sets the variable the
> tests read, such as `OPENDXP_TEST_REDIS_DSN`. The
> [README of the testkit](https://github.com/open-dxp/docker-testkit) describes the services and
> the rest of its configuration.

CI builds the application with the same commands as the testkit. A test that is green on your
machine is green in CI.

The testkit also ships an MCP server, so an AI agent can run your tests and read the results.

## Next

[Writing Tests](./03_Writing_Tests.md) takes one test from its first line to a dataset over several
kinds of element. On the way it shows where helpers, datasets and test cases of your own go.

[Core Tests](./09_Core_Tests.md) describes what the test suite of OpenDXP itself adds to this setup.
