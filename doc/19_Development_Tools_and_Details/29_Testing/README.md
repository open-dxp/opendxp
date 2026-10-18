# Testing

This chapter shows you how to test an OpenDXP project or bundle. You write the tests with Pest and
create their data with factories. Core, every bundle and every project use the same setup.

## The layers

The testing tools of OpenDXP are built in layers. Each layer builds on the layer below it. Its
documentation does not repeat the layers below:

| Layer                      | What it gives you                                                                         | Documentation                                                                |
|----------------------------|-------------------------------------------------------------------------------------------|------------------------------------------------------------------------------|
| `open-dxp/test-foundation` | The test kernel, the test cases, the browser and the commands that build the application. | [README of the test foundation](https://github.com/open-dxp/test-foundation) |
| `OpenDxp\Test` in OpenDXP  | Factories for the models of OpenDXP, Pest expectations and installers for definitions.    | This chapter                                                                 |
| The test API of a bundle   | Factories, bricks and expectations for the models of one bundle.                          | The testing page of the bundle                                               |
| Your project               | The tests of your own code, with the test APIs of the bundles you use.                    | [Testing a Project](./13_Testing_a_Project.md)                               |

The classes of `OpenDxp\Test` ship with OpenDXP itself. They need Pest and Foundry, which the test
foundation brings in.

## The pieces

| Piece                                                                        | What it gives you                                                     |
|------------------------------------------------------------------------------|-----------------------------------------------------------------------|
| [Pest](https://pestphp.com)                                                  | The test framework. A test is a function with a sentence as its name. |
| [Foundry](https://github.com/zenstruck/foundry)                              | The factories that create test data.                                  |
| [open-dxp/test-foundation](https://github.com/open-dxp/test-foundation)      | Everything a package needs to run tests, and the setup of a package.  |
| [Test API](./07_Test_API.md)                                                 | The reference of `OpenDxp\Test`.                                      |
| [OpenDXP Testkit](https://github.com/open-dxp/docker-testkit)                | Runs your tests and static checks on your machine.                    |
| [OpenDXP workflows](https://github.com/open-dxp/workflows-collection-public) | Run the tests and static checks of a bundle in CI.                    |

## Set up and run

The test foundation describes the setup in
[Set up a bundle](https://github.com/open-dxp/test-foundation#set-up-a-bundle) and
[Set up a project](https://github.com/open-dxp/test-foundation#set-up-a-project).
[Run the tests](https://github.com/open-dxp/test-foundation#run-the-tests) describes how you run the
tests.

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

## Next

| Page                                                   | Read it when                                      |
|--------------------------------------------------------|---------------------------------------------------|
| [Writing Tests](./03_Writing_Tests.md)                 | you write your first tests                        |
| [Factories and Stories](./05_Factories_and_Stories.md) | your tests need data                              |
| [Test API](./07_Test_API.md)                           | you look for a factory, a state or an expectation |
| [Shipping a Test API](./11_Bundle_Test_API.md)         | your bundle offers test classes to other packages |
| [Testing a Project](./13_Testing_a_Project.md)         | you test a project that uses several bundles      |
| [Core Tests](./09_Core_Tests.md)                       | you contribute to the test suite of OpenDXP       |
