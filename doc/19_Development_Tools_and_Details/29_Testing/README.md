# Testing

OpenDXP is tested with [Pest](https://pestphp.com). Core, every bundle and every project use the same
setup. Learn it once, and you can test anything in the OpenDXP world.

Two pieces work together:

| | |
|---|---|
| [open-dxp/test-foundation](https://github.com/open-dxp/test-foundation) | The test kernel, the test cases, and the commands that build the application your tests run in. |
| [OpenDXP Testkit](https://github.com/open-dxp/docker-testkit) | Runs your tests and static checks on your machine. |

CI builds the application with the same commands as the testkit. A test that is green on your
machine is green in CI.

## What a test looks like

```php
use OpenDxp\Model\Document;
use OpenDxp\Test\Factory\DocumentPageFactory;

it('finds a page by its path', function () {
    $page = DocumentPageFactory::createOne(['key' => 'about-us']);

    expect(Document::getByPath('/about-us')?->getId())->toBe($page->getId());
});
```

The test runs against a real OpenDXP installation with a real database. A factory creates the page,
and everything the test writes is rolled back when it ends. The next test starts clean.

## Testing your project or bundle

A package needs four things: `open-dxp/test-foundation` in `require-dev`, a `phpunit.xml.dist`, a
test kernel and a `tests/Pest.php`. The
[README of the test foundation](https://github.com/open-dxp/test-foundation) shows each of them,
along with the directory structure, the test cases, the factories and the browser.

## Running the tests

A test needs an installed OpenDXP with a database, and your bundle does not have one. A project has
one, but you do not want a test run to install, migrate or empty it. The testkit takes care of both.

It is a [DDEV](https://ddev.com) project of its own. On every run it copies your checkout into a
slot, builds a complete OpenDXP application around it, installs it into a database of its own and
runs Pest there. Nothing is written into your checkout, and your repository needs no `vendor`
directory. The first run of a package builds its slot and takes a minute or two. Every run after
that takes seconds. Up to five runs work side by side, each with its own PHP version and database.

Clone the [testkit](https://github.com/open-dxp/docker-testkit), run `ddev start` once, and call it
from anywhere inside your checkout:

```bash
testkit test
testkit test tests/Feature/Document
testkit test --filter="finds a page"
testkit test --php 8.3 --db mariadb
```

Pest options and test paths go straight to Pest.

The static checks run against the same application:

```bash
testkit analyse
testkit analyse lint
testkit analyse phpstan
testkit analyse deptrac
testkit analyse phparkitect
testkit analyse phpstan --baseline
```

`lint` checks the container, the YAML configuration and the Twig templates, and always runs.
PHPStan, Deptrac and PHPArkitect run when your package has a `phpstan.neon`, a `deptrac.yaml` or a
`phparkitect.php`. `--baseline` writes the PHPStan baseline back into your checkout.

The testkit also ships an MCP server, so an AI agent can run your tests and read the results.

## Testing OpenDXP itself

[Core Tests](./01_Core_Tests.md) describes the test suite of this repository.
