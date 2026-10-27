<?php

declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tests\Feature\DataType;

use Exception;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Data\UrlSlug;
use OpenDxp\Test\Elements;
use OpenDxp\Test\Factory\SiteFactory;
use OpenDxp\Tests\Application\Service\AvailableSitesListener;
use OpenDxp\Tests\Application\Service\FailingSlugGenerator;
use OpenDxp\Tests\Application\Service\SharedPrefixSlugGenerator;
use OpenDxp\Tests\Application\Service\ThingSlugGenerator;
use OpenDxp\Tests\Factory\SluggableFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

it('keeps the slug of every site', function () {
    $firstSite = SiteFactory::createOne();
    $secondSite = SiteFactory::createOne();
    $object = UnittestFactory::createOne();
    $definition = $object->getClass()->getFieldDefinition('urlSlug');
    $sent = [
        [0, '/fallback', ''],
        [$firstSite->getId(), '/first', ''],
        [$secondSite->getId(), '/second', ''],
    ];

    $object->setUrlSlug($definition->getDataFromEditmode($sent, $object));
    $object->save();
    $slugs = slugsBySite(Elements::refresh($object)->getUrlSlug());

    expect($slugs)->toBe([
        0 => '/fallback',
        $firstSite->getId() => '/first',
        $secondSite->getId() => '/second',
    ]);
});

it('fills an empty slug with the default slug behind the prefix', function () {
    $object = SluggableFactory::createOne(['name' => 'Green Bike']);

    expect(slugsBySite($object->getSlug()))->toBe([0 => '/things/green-bike']);
});

it('fills the slug of every language with the prefix of the language', function () {
    $object = SluggableFactory::new()
        ->withLocalizedValues(
            'lname',
            [
                'de' => 'Grünes Rad',
                'en' => 'Green Bike',
            ],
        )
        ->create();

    expect(slugsBySite($object->getLslug('de')))
        ->toBe([0 => '/de/things/gr-nes-rad'])
        ->and(slugsBySite($object->getLslug('en')))
        ->toBe([0 => '/en/things/green-bike']);
});

it('fills an empty slug when an object is saved again without touching the slug', function () {
    $object = SluggableFactory::createOne();

    $loaded = Elements::refresh($object);
    $loaded->setName('Green Bike');
    $loaded->save();
    $slugs = slugsBySite(Elements::refresh($object)->getSlug());

    expect($slugs)->toBe([0 => '/things/green-bike']);
});

it('fills an empty localized slug when an object is saved again without touching the slug', function () {
    $object = SluggableFactory::createOne();

    $loaded = Elements::refresh($object);
    $loaded->setLname('Grünes Rad', 'de');
    $loaded->save();
    $slugs = slugsBySite(Elements::refresh($object)->getLslug('de'));

    expect($slugs)->toBe([0 => '/de/things/gr-nes-rad']);
});

it('leaves the slug of a language empty that is not available', function () {
    $listener = (new AvailableSitesListener())
        ->makeUnavailable('de');

    $object = runWithAvailableSitesListener($listener, fn () => SluggableFactory::new()
        ->withLocalizedValues(
            'lname',
            [
                'de' => 'Grünes Rad',
                'en' => 'Green Bike',
            ],
        )
        ->create());

    expect(slugsBySite(
        $object
            ->getLocalizedfields()
            ->getLocalizedValue('lslug', 'de', ignoreFallbackLanguage: true),
    ))
        ->toBe([])
        ->and(slugsBySite($object->getLslug('en')))
        ->toBe([0 => '/en/things/green-bike']);
});

it('keeps a slug that is not empty', function () {
    $object = SluggableFactory::createOne([
        'name' => 'Green Bike',
        'slug' => [new UrlSlug('/my/own/path', 0)],
    ]);

    $object->setName('Red Bike');
    $object->save();
    $slugs = slugsBySite(Elements::refresh($object)->getSlug());

    expect($slugs)->toBe([0 => '/my/own/path']);
});

it('fills the slug again after an editor empties it', function () {
    $object = SluggableFactory::createOne(['name' => 'Green Bike']);

    $object->setName('Red Bike');
    $object->setSlug([new UrlSlug('', 0)]);
    $object->save();
    $slugs = slugsBySite(Elements::refresh($object)->getSlug());

    expect($slugs)->toBe([0 => '/things/red-bike']);
});

it('keeps the slugs of the sites when it fills the fallback slug', function () {
    $site = SiteFactory::createOne();

    $object = SluggableFactory::createOne([
        'name' => 'Green Bike',
        'slug' => [new UrlSlug('/site/bike', $site->getId())],
    ]);

    expect(slugsBySite($object->getSlug()))->toBe([
        0 => '/things/green-bike',
        $site->getId() => '/site/bike',
    ]);
});

it('fills an empty slug of a site with the prefix of that site', function () {
    $site = SiteFactory::createOne();

    $object = SluggableFactory::createOne([
        'name' => 'Green Bike',
        'slug' => [new UrlSlug('', $site->getId())],
    ]);

    expect(slugsBySite($object->getSlug()))->toBe([
        0 => '/things/green-bike',
        $site->getId() => sprintf('/site-%d/things/green-bike', $site->getId()),
    ]);
});

it('leaves an empty slug of a site empty when a listener takes the site away', function () {
    $site = SiteFactory::createOne();
    $listener = (new AvailableSitesListener())
        ->removeSite($site, language: null);

    $object = runWithAvailableSitesListener($listener, fn () => SluggableFactory::createOne([
        'name' => 'Green Bike',
        'slug' => [new UrlSlug('', $site->getId())],
    ]));

    expect(slugsBySite(Elements::refresh($object)->getSlug()))->toBe([0 => '/things/green-bike']);
});

it('gives a site with another prefix a slug of its own', function () {
    $site = SiteFactory::createOne();

    $object = SluggableFactory::createOne(['name' => 'Green Bike']);

    expect(slugsBySite($object->getSlug()))->toBe([
        0 => '/things/green-bike',
        $site->getId() => sprintf('/site-%d/things/green-bike', $site->getId()),
    ]);
});

it('gives a site with the prefix of the fallback slug no slug of its own', function () {
    SiteFactory::createOne();
    ClassDefinition::getByName('Sluggable')
        ->getFieldDefinition('slug')
        ->setSlugGeneratorClass(SharedPrefixSlugGenerator::class);

    $object = SluggableFactory::createOne(['name' => 'Green Bike']);

    expect(slugsBySite($object->getSlug()))->toBe([0 => '/things/green-bike']);
})->after(fn () => ClassDefinition::getByName('Sluggable')
    ->getFieldDefinition('slug')
    ->setSlugGeneratorClass(ThingSlugGenerator::class));

it('saves a filled slug without asking the generator for a prefix', function () {
    ClassDefinition::getByName('Sluggable')
        ->getFieldDefinition('slug')
        ->setSlugGeneratorClass(FailingSlugGenerator::class);

    $object = SluggableFactory::createOne([
        'name' => 'Green Bike',
        'slug' => [new UrlSlug('/bike')],
    ]);

    expect(slugsBySite(Elements::refresh($object)->getSlug()))->toBe([0 => '/bike']);
})->after(fn () => ClassDefinition::getByName('Sluggable')
    ->getFieldDefinition('slug')
    ->setSlugGeneratorClass(ThingSlugGenerator::class));

it('leaves the slug empty when there is no default slug', function () {
    $object = SluggableFactory::createOne();

    expect(slugsBySite($object->getSlug()))->toBe([]);
});

it('extends a slug that another object uses', function () {
    SluggableFactory::createOne(['name' => 'Green Bike']);

    $object = SluggableFactory::createOne(['name' => 'Green Bike']);

    expect(slugsBySite($object->getSlug()))->toBe([0 => '/things/green-bike-1']);
});

it('counts the extension up until the slug is free', function () {
    SluggableFactory::createMany(2, ['name' => 'Green Bike']);

    $object = SluggableFactory::createOne(['slug' => [new UrlSlug('/things/green-bike', 0)]]);

    expect(slugsBySite($object->getSlug()))->toBe([0 => '/things/green-bike-2']);
});

it('keeps its own slug when an object is saved again', function () {
    $object = SluggableFactory::createOne(['name' => 'Green Bike']);

    $object->setName('Red Bike');
    $object->save();
    $slugs = slugsBySite(Elements::refresh($object)->getSlug());

    expect($slugs)->toBe([0 => '/things/green-bike']);
});

it('rejects a slug that another object uses when the field does not extend it', function () {
    SluggableFactory::createOne(['strictSlug' => [new UrlSlug('/taken', 0)]]);

    SluggableFactory::createOne(['strictSlug' => [new UrlSlug('/taken', 0)]]);
})->throws(Exception::class, 'Slug "/taken" is already used by object');
