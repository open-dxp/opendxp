# Testing a Project

A project tests its own code with the test APIs of OpenDXP and of the bundles it uses. This page
shows how one suite combines the setup of several bundles. It builds on
[Set up a project](https://github.com/open-dxp/test-foundation#set-up-a-project). The examples use
two invented bundles, `Acme\BlogBundle` and `Acme\AccountBundle`.

## What every project has

Every project has the test kernel in `tests/TestKernel.php`, a `phpunit.xml.dist` and a
`tests/Pest.php`, as the test foundation describes. `tests/Pest.php` registers the expectations of
OpenDXP. [Test API](./07_Test_API.md#registration) shows how.

The testing page of each bundle adds a few lines to these files. The sections below show where these
lines go.

## Expectations of several bundles

`tests/Pest.php` registers the expectations of OpenDXP and of every bundle:

```php
<?php

declare(strict_types=1);

use Acme\AccountBundle\Test\Expectation\Accounts;
use Acme\BlogBundle\Test\Expectation\Posts;
use OpenDxp\Test\Expectation\Fields;
use OpenDxp\TestFoundation\TestCase;

Fields::register();
Posts::register();
Accounts::register();

pest()->extend(TestCase::class)->in('Feature');
```

## Extensions of several bundles

`phpunit.xml.dist` registers the extensions of the bundles after the extensions of DAMA and Foundry:

```xml
<extensions>
    <bootstrap class="DAMA\DoctrineTestBundle\PHPUnit\PHPUnitExtension"/>
    <bootstrap class="Zenstruck\Foundry\PHPUnit\FoundryExtension"/>
    <bootstrap class="Acme\BlogBundle\Test\PHPUnit\InstallPostClass"/>
</extensions>
```

## Configuration of several bundles

`tests/config/test.yaml` imports the test configuration of each bundle that has one. It also holds
the lines that the testing pages of the bundles ask for:

```yaml
imports:
    - { resource: '@AcmeBlogBundle/config/test/services.yaml' }
    - { resource: '@AcmeAccountBundle/config/test/services.yaml' }

acme_account:
    login_link_lifetime: 300
```

## A user factory for several bundles

A project has one user class and one user factory. The factory extends the base class that one
bundle ships. It adds the states that the other bundles ask for. In this example, the account bundle
ships the base class. The testing page of the blog bundle asks for the state `writingFor()`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use Acme\AccountBundle\Test\Factory\AbstractAccountUserFactory;
use OpenDxp\Model\DataObject\User;

/**
 * @extends AbstractAccountUserFactory<User>
 */
final class UserFactory extends AbstractAccountUserFactory
{
    public static function class(): string
    {
        return User::class;
    }

    public function writingFor(string $blog): static
    {
        return $this->with(['blog' => $blog]);
    }
}
```

## Factories of the project

Each data object class of the project gets its own factory in `tests/Factory/`. The factory extends
`AbstractDataObjectFactory`. [Write the factory](./05_Factories_and_Stories.md#write-the-factory)
shows how.
