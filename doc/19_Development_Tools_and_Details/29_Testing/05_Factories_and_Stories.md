# Factories and Stories

On this page you create the data your tests need. You start with the factories OpenDXP ships for
its own models, fill a page with bricks, and then write a factory for a data object class of your
own. At the end you build a story, a fixed world that several tests share. Everything a factory
writes lives inside the transaction of the test and is gone when the test ends.

The factories are [Foundry](https://github.com/zenstruck/foundry) factories. They need the Foundry
extension in `phpunit.xml.dist`, as [Testing](./README.md#set-up-your-package) shows.

## Create a model

A factory creates a model, saves it and hands it back:

```php
use OpenDxp\Test\Factory\DocumentPageFactory;

$page = DocumentPageFactory::createOne();
```

The page is valid and published, and it sits below the root document. Its key is a random slug.
Every call creates new random values, so two pages never collide, and a test never depends on a
value it did not name.

When a value matters to the test, name it:

```php
$page = DocumentPageFactory::createOne(['key' => 'about-us']);
```

`createMany()` creates several models with their own random values:

```php
$pages = DocumentPageFactory::createMany(3);
```

## Describe it with states

Most tests need more than a page with a key. They need a page below another page, a page that is not
published, or a page in one language. A factory says this with states. A state is a method in the
language of the subject:

```php
$parent = DocumentPageFactory::createOne(['key' => 'en']);

$child = DocumentPageFactory::new()
    ->withParent($parent)
    ->unpublished()
    ->withLocale('en')
    ->create(['key' => 'about-us']);
```

`new()` starts a chain, every state adds to it, and `create()` ends it. `createOne()` and
`createMany()` are static calls for the simple case without states. At the end of a chain they are
deprecated, and Foundry 3 refuses them. Use `create()` and `many()->create()` there.

The factory saves the model as the very last step. Every state therefore takes effect before the
model is written.

When a state exists, use it. Do not write `['parentId' => $parent->getId()]` when there is
`withParent()`, and do not change a model with a setter and save it again when a state says the
same. [Test API](./07_Test_API.md#states) lists every state of every factory.

`unsaved()` leaves out the write. The test gets a model that is not in the database:

```php
$page = DocumentPageFactory::new()
    ->unsaved()
    ->create();
```

A state that needs the id of the model does nothing on an unsaved model. `withTranslationOf()`
links no translation, and a site gets no root document.

> [!NOTE]
> The factories of OpenDXP are `ObjectFactory` classes of Foundry, not `PersistentObjectFactory`
> classes. Foundry's repository methods such as `find()`, `random()`, `count()`, `assert()` and
> `truncate()` do not exist here, and neither does `withoutPersisting()`. Load a model through
> OpenDXP, for example with `Page::getById()`, and check it with expectations.

## Create several at once

`many()` repeats a chain:

```php
$pages = DocumentPageFactory::new()
    ->withParent($parent)
    ->many(2)
    ->create();
```

When the models differ in fixed ways, give each of them its values with `sequence()`:

```php
$pages = DocumentPageFactory::new()
    ->withParent($parent)
    ->sequence([
        ['key' => 'news'],
        ['key' => 'events'],
    ])
    ->create();
```

`distribute()` spreads a list of values over one field:

```php
$pages = DocumentPageFactory::new()
    ->distribute('key', ['news', 'events'])
    ->create();
```

## Fill a page with content

Pages, snippets and emails take editables. `withEditables()` puts editables on the document under
the names you give them:

```php
use OpenDxp\Model\Document\Editable\Input;

$headline = new Input();
$headline->setDataFromResource('Imprint');

$page = DocumentPageFactory::new()
    ->withEditables(['headline' => $headline])
    ->create();
```

Every document the factory creates gets editables of its own, even with `many()`.

Most pages hold their content in areabricks. `withBricks()` fills one areablock of the template.
Its first argument names the areablock, and the bricks follow in the order they appear on the page:

```php
use OpenDxp\Tests\Application\Brick\GreetingBrick;

$page = DocumentPageFactory::new()
    ->withBricks(
        'content',
        GreetingBrick::saying('Hello'),
        GreetingBrick::saying('Goodbye'),
    )
    ->create();
```

A brick is one instance of an areabrick, with the values of its editables. It implements
`OpenDxp\Test\Document\Brick`. `id()` returns the id the areabrick is registered with.
`editables()` returns its editables, keyed by the names the template of the areabrick uses. This is
the brick of an areabrick `greeting` whose template renders `{{ opendxp_input('text') }}`:

```php
use OpenDxp\Model\Document\Editable\Input;
use OpenDxp\Test\Document\Brick;

final readonly class GreetingBrick implements Brick
{
    private function __construct(private string $text)
    {
    }

    public static function saying(string $text): self
    {
        return new self($text);
    }

    public function id(): string
    {
        return 'greeting';
    }

    public function editables(): array
    {
        $text = new Input();
        $text->setDataFromResource($this->text);

        return ['text' => $text];
    }
}
```

A bundle that ships areabricks ships a brick class for each of them, in its namespace `Test\Brick`.
A project that uses the bundle then builds its pages with the bricks of the bundle.

A page renders through the default controller of the test application unless you name another one:

```php
use Acme\BlogBundle\Tests\Application\Controller\TeaserController;

$page = DocumentPageFactory::new()
    ->withController(TeaserController::class, 'listAction')
    ->create();
```

## Data objects of your own classes

Your package has data object classes of its own. The rest of this page uses a blog bundle with a
class `Post`. A post has a `title`, a localized `teaser` and an `author`, which is an object of the
class `Author`.

### Install the class

Export the class definition from the class editor and put it into `tests/Fixtures/classes/`. The
file name is the name of the class:

```
tests/Fixtures/
    classificationstores/
    fieldcollections/
    classes/Post.json
    objectbricks/
```

The extension `InstallDefinitions` of the test foundation installs every definition it finds there,
once, before the first test. Classes refer to classification stores and field collections, and
object bricks refer to classes. The extension therefore installs classification stores first, then
field collections, classes and object bricks.

A classification store has a file format of its own. The file describes the store, its groups and
the keys of each group. The type of a key is the name of a field type:

```json
{
    "description": "The store a post keeps its facts in.",
    "groups": {
        "facts": {
            "readingTime": {
                "type": "numeric",
                "description": "Minutes it takes to read the post."
            }
        }
    }
}
```

### Write the factory

A factory for a data object class extends `AbstractDataObjectFactory`. It lives in `tests/Factory/`:

```php
namespace Acme\BlogBundle\Tests\Factory;

use OpenDxp\Model\DataObject\Post;
use OpenDxp\Test\Factory\AbstractDataObjectFactory;

/**
 * @extends AbstractDataObjectFactory<Post>
 */
final class PostFactory extends AbstractDataObjectFactory
{
    public static function class(): string
    {
        return Post::class;
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'title' => self::faker()->sentence(),
        ];
    }
}
```

`PostFactory` inherits the states of every data object factory. A post with its teaser in two
languages looks like this:

```php
$post = PostFactory::new()
    ->withLocalizedValues(
        'teaser',
        [
            'en' => 'English teaser',
            'de' => 'German teaser',
        ],
    )
    ->create();
```

`withFieldcollection()`, `withObjectbrick()` and `withClassificationValues()` fill a field
collection, an object brick and a classification store in the same way.

## Write a good factory

A factory is part of the language of your tests. These habits keep that language clear.

**One factory per model.** Name it after the class it builds, and make it `final`. A base class is
`abstract`. The `@extends` line gives the model class to Foundry and to PHPStan. `@method` lines
only repeat what Foundry already declares, so leave them out.

**Every required value in `defaults()`.** A model from `createOne()` without arguments is valid.
The values are random and realistic, and they come from `self::faker()` inside the array. Foundry
calls `defaults()` for every model it creates, so every model gets values of its own. A closure as
a single value in `defaults()` is not called. Leave out what the model sets itself, such as `type`.

**Nothing is created in `defaults()`.** A model the new one depends on is a factory, and Foundry
creates it only when the test does not pass one:

```php
protected function defaults(): array
{
    return [
        ...parent::defaults(),
        'title'  => self::faker()->sentence(),
        'author' => AuthorFactory::new(),
    ];
}
```

A value with a side effect is wrapped in `lazy()`. A model that every created model shares is
`AuthorFactory::new()->memoize()`. A file that `defaults()` reads is wrapped in `lazy()` too, so the
factory reads it only when the test does not replace it. The asset factories of OpenDXP do this:

```php
'data' => lazy(static fn () => file_get_contents(self::fixture())),
```

**States instead of options.** A state takes only required arguments and no boolean flag. Every
variant has a name of its own. The thumbnail factory has `scalingByWidth(256)` and
`enlargingToWidth(256)`, not `scalingByWidth(256, true)`. A state returns `$this->with([...])` or
adds a hook. A random value in a state goes into a closure, or every model of `many()` shares it.
The redirect factory does this:

```php
public function scheduled(): static
{
    return $this->with(static fn (): array => [
        'validFrom' => self::faker()->dateTimeBetween('+1 hour', '+1 year')->getTimestamp(),
    ]);
}
```

When a test needs a state that does not exist, add it to the factory. Do not work around it in the
test.

**Hooks run before the write.** A hook is a function that Foundry calls while it builds the model.
The base class `AbstractSavingFactory` saves the model in a hook
with the priority `WRITE`. Foundry runs hooks from the highest priority down, so every hook of a
state runs before the model is written. A hook that needs the id of the model uses
`afterWriting()`. It runs after the write and only when the factory writes:

```php
public function assignedTo(ElementInterface ...$elements): static
{
    return $this->afterWriting(
        static function (Tag $tag) use ($elements): void {
            foreach ($elements as $element) {
                Tag::addTagToElement(
                    Service::getElementType($element),
                    $element->getId(),
                    $tag,
                );
            }
        },
    );
}
```

**A fixed order.** A factory lists `class()`, then the states, then `defaults()`, then
`initialize()`, then its private methods.

A factory for a model of your test application lives in `tests/Factory/`. A bundle whose models
other packages create in their tests ships those factories in its own code, the way OpenDXP ships
`OpenDxp\Test\Factory`.

## Share a world with a story

Factories produce random data. Some tests need the opposite: a fixed world with known values that
several tests read. That is a story:

```php
namespace Acme\BlogBundle\Tests\Story;

use OpenDxp\Model\Document\Page;
use OpenDxp\Model\Site;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\SiteFactory;
use Zenstruck\Foundry\Story;

/**
 * @method static Site siteA()
 * @method static Site siteB()
 * @method static Page section()
 */
final class TwoSites extends Story
{
    public function build(): void
    {
        $siteA = SiteFactory::createOne(['mainDomain' => 'domain-a.test']);
        $siteB = SiteFactory::createOne(['mainDomain' => 'domain-b.test']);
        $section = DocumentPageFactory::new()
            ->withParent($siteA->getRootDocument())
            ->create(['key' => 'section']);

        $this->addState('siteA', $siteA);
        $this->addState('siteB', $siteB);
        $this->addState('section', $section);
    }
}
```

A story remembers its models with `addState()`. A test reaches them through
`TwoSites::get('section')` or through the magic call `TwoSites::section()`. The magic call loads the
story if it is not loaded yet. Each magic call has its `@method` line, so your IDE and PHPStan know
its type.

Load a story in `beforeEach` when every test of the file needs it:

```php
beforeEach(fn () => TwoSites::load());

it('builds the path of a page inside its site', function () {
    $path = TwoSites::section()->getFullPath();

    // ...
});
```

A story builds its world once per test, however often it is loaded. A story that needs another one
calls `OtherStory::load()` in its `build()`. A test case can load a story for all of its tests with
Foundry's attribute `#[WithStory(TwoSites::class)]`. Stories live in `tests/Story/`.

## Repeat a failing run

Random values can make a test fail only once in a while. Foundry prints the seed of its random
values after every run:

```
Faker seed used: 1234
```

Set the environment variable `FOUNDRY_FAKER_SEED` to that number, for example in the `<php>` section
of `phpunit.xml.dist`. Every factory then produces the same values again. Narrow the run to the
failing test with `--filter`.

## Next

[Test API](./07_Test_API.md) lists every factory, every state, every expectation and every installer
that OpenDXP ships, with what it does. Come back to it whenever you look for a state.
