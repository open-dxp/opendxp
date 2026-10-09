# Writing Tests

On this page you write one test and let it grow. It starts as a single check on the children of a
page and ends as one test that covers documents and data objects alike. On the way you learn where
each piece of test code belongs and how a test keeps out of the way of the next one.

The page assumes a package that is set up as [Testing](./README.md) describes. The examples use
the factories of OpenDXP. [Factories and Stories](./05_Factories_and_Stories.md) explains them in
detail.

## Put the test in its place

A test that needs the application lives under `tests/Feature`. A test that needs nothing but PHP
lives under `tests/Unit`. Unit tests get no test case, so they start no kernel and stay fast.

Every subject has a directory, and every file holds the tests of one subject. The file name names
the subject and ends in `Test.php`. The children of a document go into
`tests/Feature/Document/ChildrenTest.php`.

## Write the test

```php
use OpenDxp\Test\Factory\DocumentPageFactory;

it('lists only the published children', function () {
    $parent = DocumentPageFactory::createOne();
    DocumentPageFactory::new()
        ->withParent($parent)
        ->create();
    DocumentPageFactory::new()
        ->withParent($parent)
        ->unpublished()
        ->create();

    $children = $parent->getChildren()->getDocuments();

    expect($children)->toHaveCount(1);
});
```

The name is a sentence that says what is guaranteed. Pest puts `it` in front of it, so it reads
*it lists only the published children*. Keep the sentence short and factual, and make sure it
matches what the test checks.

The body has three blocks, separated by an empty line. The first prepares, the second acts, and the
third checks. The first block starts right after `function () {`, without an empty line. A test has
exactly one action. If you need a second action, you need a second test.

Two things stay out of a test:

- A check before the action. If the parent had no children to begin with, the result would show it.
- A check of PHP itself or of a plain setter and getter. A test checks what OpenDXP does.

## Check more than one thing

A test may check several results of its one action. Chain the checks with `->and()`:

```php
it('lists only the published children', function () {
    $parent = DocumentPageFactory::createOne();
    $published = DocumentPageFactory::new()
        ->withParent($parent)
        ->create();
    DocumentPageFactory::new()
        ->withParent($parent)
        ->unpublished()
        ->create();

    $children = $parent->getChildren()->getDocuments();

    expect($children)
        ->toHaveCount(1)
        ->and($children[0]->getId())
        ->toBe($published->getId());
});
```

A single check stays on one line. From two checks on, every call of the chain goes on a line of its
own. The same holds for a factory chain that has more than one call.

A getter of the subject can go straight into the chain. Pest calls it on the value and checks the
result. This is how the suite of OpenDXP checks a link document:

```php
expect($link)
    ->toBeInstanceOf(Link::class)
    ->getInternal()
    ->toBe($target->getId());
```

Use the expectations of Pest, not the assertions of PHPUnit. If a model is loaded from the
database, load it once into a variable and check that variable. Do not load it again in every line
of the chain.

An exception is checked with `->throws()` when the whole test is the action that throws:

```php
it('refuses a value longer than its column', function () {
    $object = UnittestFactory::new()
        ->unsaved()
        ->create(['input' => str_repeat('x', 500)]);

    $object->save();
})->throws(ValidationException::class, 'Value in field [ input ] is longer than 190 characters');
```

In every other case, wrap the action in a closure:

```php
expect(fn () => $this->handler->save($key, 'test'))
    ->toThrow(InvalidArgumentException::class, 'contains reserved characters');
```

### The expectations of OpenDXP

OpenDXP brings expectations for checks that Pest cannot make on its own. You registered them in
`tests/Pest.php` when you set up your package.

`toCarryField()` checks that a data object carries the value it was given. Every field type has its
own idea of equality, so the expectation asks the field definition instead of comparing the values
directly:

```php
expect(reloaded($object))->toCarryField('consent', new Consent(true));
```

`toCarryLocalizedField()` does the same for one language of a localized field:

```php
expect(reloaded($object))
    ->toCarryLocalizedField('linput', $germanValue, 'de')
    ->toCarryLocalizedField('linput', $englishValue, 'en');
```

The redirect expectations check the response to a request. `toRedirectTo()` checks the location.
A location that starts with a slash is compared with the path and the query only, so the test does
not depend on the host. `toBeAnsweredBy()` names the redirect that answered, and
`toBeAnsweredWithoutRedirect()` checks that none did:

```php
expect($response)
    ->toRedirectTo('/new-page')
    ->toBeAnsweredBy($redirect);
```

[Test API](./07_Test_API.md#expectations) lists all of them.

## Cover data objects with the same test

Data objects have children too, and an unpublished object is left out in the same way as an
unpublished document. You could copy the test and swap the factory. Two tests for the same
behaviour drift apart over time, so write one test with a dataset instead:

```php
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

dataset('publishable elements', [
    'a document' => DocumentPageFactory::class,
    'an object' => UnittestFactory::class,
]);

it('lists only the published children', function (string $factory) {
    $parent = $factory::createOne();
    $factory::new()
        ->withParent($parent)
        ->create();
    $factory::new()
        ->withParent($parent)
        ->unpublished()
        ->create();

    $children = $parent->getChildren()->getDocuments();

    expect($children)->toHaveCount(1);
})->with('publishable elements');
```

`UnittestFactory` builds objects of a class in the suite of OpenDXP. In your package, the factory
of one of your own classes takes its place.

Pest runs the test once per entry and shows the key in the name of the test. Give every entry a key
that reads as a case, such as `a document` or `an object`.

A dataset changes the data of a test, never its course. If the test needs an `if`, a `match` or a
method name that depends on the dataset, the cases are different behaviours. They get tests of
their own.

A dataset that hands over an object hands it over as a closure. Pest calls the closure after
`beforeEach`, so the closure can use what `beforeEach` put on `$this`, and the parameter of the
test keeps its type:

```php
dataset('descendants', [
    'the child' => fn () => $this->child,
    'the grandchild' => fn () => $this->grandchild,
]);

it('gives a brick value of the parent to every descendant', function (Inheritance $descendant) {
    // ...
})->with('descendants');
```

The scope of a dataset follows its use. A named dataset is only visible in its scope.

| Used by                    | Where it goes                       |
|----------------------------|-------------------------------------|
| one test                   | inline, with `->with([...])`        |
| several tests of one file  | `dataset('name', ...)` in that file |
| the tests of one directory | `Datasets.php` in that directory    |
| the whole suite            | a file under `tests/Datasets/`      |

## Share the setup

Once several tests in the file need the same parent, move it into `beforeEach`. State that
`beforeEach` prepares for the tests of a file goes on `$this`:

```php
beforeEach(function () {
    $this->parent = DocumentPageFactory::createOne();
});

it('leaves out an unpublished child', function () {
    DocumentPageFactory::new()
        ->withParent($this->parent)
        ->unpublished()
        ->create();

    $children = $this->parent->getChildren();

    expect($children)->toBeEmpty();
});
```

Wrap tests in `describe()` only when a group of them shares a setup or a dataset that the rest of
the file does not need.

## Helpers, values and expectations of your own

Sooner or later a test needs a small piece of code more than once. A function that keeps no state
goes into the test file, below the `use` lines. Once a second file needs it, it moves to
`tests/Helpers/`, into a file named after its subject in lowercase. A file name in PascalCase
would promise a class. There is never a second helper for the same thing.

The suite of OpenDXP has a helper that loads an element the way the next request would:

```php
// tests/Helpers/element.php

function reloaded(ElementInterface $element): ElementInterface
{
    RuntimeCache::clear();

    return $element::getById(
        $element->getId(),
        ['force' => true],
    );
}
```

A helper does real work. It never wraps a single getter, and it holds no expectation. A check that
repeats is an expectation of your own. It goes into `tests/Expectations/`, its name follows the
pattern of Pest (`toBe...`, `toHave...`, `toCarry...`), and it ends with `return $this`.

Nothing returns an array whose keys describe a shape. That shape is a class. Put it under
`tests/Value/` as a `final readonly` class:

```php
final readonly class SearchAnswer
{
    /**
     * @param list<string> $paths
     */
    public function __construct(
        public array $paths,
        public int $total,
    ) {
    }
}
```

This table sums up where test code goes:

| What | Where |
|---|---|
| State that `beforeEach` shares with the tests of a file | `$this` |
| Behaviour that the tests of several directories share | A method of their test case, reached through `$this` |
| A helper without state for one test file | A function in that file, below the `use` lines |
| A helper without state for several test files | A function in `tests/Helpers/<subject>.php` |
| Something that builds a model and saves it | A factory |
| A check that repeats | An expectation in `tests/Expectations/` |
| Something other packages need for their tests too | A class with one task in the package itself, like `OpenDxp\Test` |

## The layout of a suite

The directories of a suite follow from the table above:

```
tests/
    Pest.php        assigns the test cases and registers the expectations
    Feature/        tests that boot the application, one directory per subject
    Unit/           tests that boot nothing
    Application/    the application of a bundle: kernel, configuration, templates, controllers,
                    services
    Factory/        factories for the models of this package
    Story/          fixed worlds with fixed values
    TestCase/       the test cases of this suite, not final
    Datasets/       datasets for the whole suite
    Helpers/        functions, no classes
    Expectations/   expectations of this suite
    Value/          value classes of this suite, final readonly
    Fixtures/       data files and definitions
```

A project has no `Application/` directory. Its test kernel lives in `tests/TestKernel.php`. Leave
out every directory you do not need. An empty directory says nothing.

Pest loads `tests/Pest.php`, everything under `tests/Helpers/` and `tests/Expectations/`, every
file named `Datasets.php` and everything under a directory `Datasets/` on its own. You never
include any of them by hand.

A test case of your own is not `final`, because Pest extends it for every test file.
`tests/Pest.php` assigns it to directories. Two assignments with different test cases must not
overlap, or Pest refuses to start. List the directories of the default test case one by one, one per
line:

```php
pest()->extend(TestCase::class)->in(
    'Feature/Document',
    'Feature/Element',
);
pest()->extend(SchemaTestCase::class)->in('Feature/Schema');
```

## Format the code

Every file of a suite should look as if one person wrote it. These habits get you there:

- A chain that builds or checks something puts every call on a line of its own, once it has two
  calls. A single check such as `expect($count)->toBe(2)` stays on one line.
- A chain that only fetches a value stays on one line: `$page->getRootDocument()->getId()`. Inside a
  longer expression it goes into `sprintf()` rather than a concatenation with `.`.
- An array with two or more entries puts every entry on a line of its own. A dataset entry may be a
  tuple of plain values on one line: `'a parent id of zero' => [0, 1]`.
- Arguments go one per line once one of them is not a plain value, or once the line gets longer than
  120 characters.
- A boolean or `null` whose meaning you cannot see at the call gets its name:
  `getChildren(includingUnpublished: true)`.
- A comment explains a reason that the code cannot show. It never repeats the code.

## Keep tests apart

Every test runs in a database transaction that is rolled back when the test ends. Whatever a test
writes through the database is gone for the next test.

The test case of the foundation also resets what OpenDXP keeps in statics: the runtime cache, the
system configuration, the admin mode, the current site and the current static route. A test can
therefore override a system setting without cleaning up:

```php
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Settings;

it('applies a system setting a test overrides', function () {
    $asset = AssetImageFactory::createOne();
    Settings::override([
        'assets' => [
            'frontend_prefixes' => [
                'source' => 'https://cdn.example.test',
            ],
        ],
    ]);

    $path = $asset->getFullPath();

    expect($path)->toBe(sprintf('https://cdn.example.test%s', $asset->getRealFullPath()));
});
```

Any other static or global state a test changes, it restores in `afterEach()` or `->after()`:

```php
it('writes no version while versioning is off', function () {
    $object = UnittestFactory::createOne();
    Version::disable();

    $object->save();

    expect(versionCountOf($object))->toBe(1);
})->after(fn () => Version::enable());
```

### Tests that cannot be rolled back

Some tests cannot run inside a transaction. A change to the schema commits the transaction, and
InnoDB writes a full text index only on commit. Such tests get a test case of their own that skips
the rollback:

```php
use DAMA\DoctrineTestBundle\PHPUnit\SkipDatabaseRollback;
use OpenDxp\TestFoundation\TestCase;

// DDL commits the transaction, so a test that changes the schema cannot be rolled back.
#[SkipDatabaseRollback]
abstract class SchemaTestCase extends TestCase
{
}
```

> [!IMPORTANT]
> Without the rollback, nothing cleans up for you. A test under such a test case removes what it
> wrote, in `afterEach()` or in the `tearDown()` of its test case. Anything it leaves behind is
> still there for the next run.

### Tests that need another configuration

Keep the configuration of the test application free of what only some tests need. A test that needs
another configuration runs under a test case that extends `EnvironmentTestCase`. It names a Symfony
environment of its own:

```php
use OpenDxp\TestFoundation\EnvironmentTestCase;

abstract class HttpCacheTestCase extends EnvironmentTestCase
{
    protected static function environment(): string
    {
        return 'http_cache';
    }
}
```

The test kernel loads the file `config/<environment>.yaml` from its own directory. For a bundle
that is `tests/Application/config/http_cache.yaml`. The configuration of the default environment
`test` lives in `test.yaml` beside it. The new file imports `test.yaml`, so the environment adds to
the test configuration instead of replacing it:

```yaml
imports:
    - { resource: test.yaml }

opendxp:
    http_cache:
        enabled: true
```

### Tests that need a service

A test against an external service, such as Redis, skips itself when the variable that names the
service is missing:

```php
function redisDsn(): string
{
    $dsn = getenv('OPENDXP_TEST_REDIS_DSN');
    if (!is_string($dsn) || $dsn === '') {
        test()->markTestSkipped('OPENDXP_TEST_REDIS_DSN names no Redis server.');
    }

    return $dsn;
}
```

A condition that is only known after `beforeEach` goes into `->skip()` as a closure:
`->skip(fn () => ..., 'reason')`.

### Out of scope

A bundle does not test its own migrations or its own installer. No test only checks that the test
application installs. The application is installed before every run, so a broken installation fails
there already.
`only()`, `todo()`, `dd()` and `ray()` never reach the repository.

## Next

[Factories and Stories](./05_Factories_and_Stories.md) shows how the factories you used here work.
It covers pages with bricks, data objects of your own classes, and how you write a factory and a
story yourself.
