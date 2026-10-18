# Shipping a Test API

A bundle can ship a test API, just as OpenDXP ships `OpenDxp\Test`. A project then uses these
classes to create test data for the models of the bundle. This page builds on
[Factories and Stories](./05_Factories_and_Stories.md). The examples use a bundle called
`Acme\BlogBundle`.

## The namespace

The test API lives in the namespace `Test` of the bundle, below `src/`:

```
src/Test/
    Factory/        factories for the models of the bundle
    Story/          fixed worlds, like the data the installer of the bundle sets up
    Brick/          a brick for each areabrick of the bundle
    Expectation/    Pest expectations, one class per subject
    PHPUnit/        PHPUnit extensions that prepare the database before the first test
```

Composer loads these classes together with the rest of the bundle. Every package that installs the
bundle can use them. Leave out the directories you do not need.

## Dependencies

The bundle requires `open-dxp/test-foundation` in `require-dev`. The foundation brings Pest and
Foundry, which the test API needs. Every package that uses the test API has the foundation as well.

Some libraries are only needed by the test API or by the tests. Put them under the key that
[Composer keys](https://github.com/open-dxp/test-foundation#composer-keys) names, never under
`suggest`. A project does not get these libraries automatically. The testing page of the bundle
names each of them, and the project requires them itself.

## Factories, stories and bricks

Write factories as [Write a good factory](./05_Factories_and_Stories.md#write-a-good-factory)
describes. Write stories as [Share a world with a story](./05_Factories_and_Stories.md#share-a-world-with-a-story)
describes.

A brick implements `OpenDxp\Test\Document\Brick`.
[Fill a page with content](./05_Factories_and_Stories.md#fill-a-page-with-content) shows how. A
project then places the bricks of the bundle on its pages:

```php
use Acme\BlogBundle\Test\Brick\TeaserBrick;
use OpenDxp\Test\Factory\DocumentPageFactory;

$page = DocumentPageFactory::new()
    ->withBricks('content', TeaserBrick::showing($post))
    ->create();
```

## Expectations

An expectation class adds its expectations to Pest in a static method `register()`:

```php
namespace Acme\BlogBundle\Test\Expectation;

use Pest\Expectation;

final class Posts
{
    public static function register(): void
    {
        expect()->extend('toBeFeatured', function (): Expectation {
            expect($this->value->isFeatured())->toBeTrue();

            return $this;
        });
    }
}
```

A package that wants these expectations calls `register()` in its `tests/Pest.php`:

```php
Acme\BlogBundle\Test\Expectation\Posts::register();
```

## PHPUnit extensions

Some bundles need a definition in the database before the tests run, for example a data object
class. A PHPUnit extension installs it. Installing a definition changes the database schema, and a
schema change inside a test would end the transaction of that test. That is why the extension runs
once, before the first test. It boots the kernel with
[`BootKernelTrait`](https://github.com/open-dxp/test-foundation#install-something-else-before-the-first-test)
of the test foundation:

```php
namespace Acme\BlogBundle\Test\PHPUnit;

use OpenDxp\Test\ClassDefinitions;
use OpenDxp\TestFoundation\PHPUnit\BootKernelTrait;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

final class InstallPostClass implements Extension
{
    use BootKernelTrait;

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $kernel = self::bootKernel();

        ClassDefinitions::install('Post', __DIR__ . '/../../../config/install/Post.json');

        $kernel->shutdown();
    }
}
```

A package that uses the bundle registers the extension in its `phpunit.xml.dist`, after the
extensions of DAMA and Foundry:

```xml
<bootstrap class="Acme\BlogBundle\Test\PHPUnit\InstallPostClass"/>
```

## The testing page

The bundle describes its test API on its own testing page. A bundle without a test API needs no
testing page, unless its own tests need a special setup.

```
# Testing

<Two or three sentences: what the bundle brings for tests, in which namespace.>
This page builds on [Testing](<link to this chapter>), or on the testing page of the bundle it extends.

## Setup                      only the lines of this bundle: a library, a register() line,
                              an extension, configuration, a class the project creates
## <Task>                     one heading per task
## Test API                   a table: class, what it gives, states
## Failures                   only the messages of this bundle
## The suite of this bundle   for contributors, only what differs from the usual suite
```
