<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\DataType;

use Exception;
use OpenDxp\Model\DataObject\Data\UrlSlug;
use OpenDxp\Model\DataObject\Sluggable;
use OpenDxp\Test\Factory\SiteFactory;
use OpenDxp\Tests\Factory\SluggableFactory;

it('fills an empty slug with the default slug behind the prefix', function () {
    $object = SluggableFactory::createOne([
        'name' => 'Green Bike',
    ]);

    expect(slugsBySite($object->getSlug()))
        ->toBe([0 => '/things/green-bike']);
});

it('fills the slug of every language with the prefix of the language', function () {
    $object = SluggableFactory::new()
        ->withLocalizedNames([
            'de' => 'Grünes Rad',
            'en' => 'Green Bike',
        ])
        ->create();

    expect(slugsBySite($object->getLslug('de')))
        ->toBe([0 => '/de/things/gr-nes-rad'])
        ->and(slugsBySite($object->getLslug('en')))
        ->toBe([0 => '/en/things/green-bike']);
});

it('fills an empty slug when an object is saved again without touching the slug', function () {
    $object = SluggableFactory::createOne();

    $saved = Sluggable::getById($object->getId(), ['force' => true]);
    $saved->setName('Green Bike');
    $saved->save();

    expect(slugsBySite(Sluggable::getById($object->getId(), ['force' => true])->getSlug()))
        ->toBe([0 => '/things/green-bike']);
});

it('fills an empty localized slug when an object is saved again without touching the slug', function () {
    $object = SluggableFactory::createOne();

    $saved = Sluggable::getById($object->getId(), ['force' => true]);
    $saved->setLname('Grünes Rad', 'de');
    $saved->save();

    expect(slugsBySite(Sluggable::getById($object->getId(), ['force' => true])->getLslug('de')))
        ->toBe([0 => '/de/things/gr-nes-rad']);
});

it('keeps a slug that is not empty', function () {
    $object = SluggableFactory::createOne([
        'name' => 'Green Bike',
        'slug' => [new UrlSlug('/my/own/path', 0)],
    ]);

    $object->setName('Red Bike');
    $object->save();

    expect(slugsBySite(Sluggable::getById($object->getId(), ['force' => true])->getSlug()))
        ->toBe([0 => '/my/own/path']);
});

it('fills the slug again after an editor empties it', function () {
    $object = SluggableFactory::createOne([
        'name' => 'Green Bike',
    ]);

    $object->setName('Red Bike');
    $object->setSlug([new UrlSlug('', 0)]);
    $object->save();

    expect(slugsBySite(Sluggable::getById($object->getId(), ['force' => true])->getSlug()))
        ->toBe([0 => '/things/red-bike']);
});

it('keeps the slugs of the sites when it fills the fallback slug', function () {
    $site = SiteFactory::createOne();

    $object = SluggableFactory::createOne([
        'name' => 'Green Bike',
        'slug' => [new UrlSlug('/site/bike', $site->getId())],
    ]);

    expect(slugsBySite($object->getSlug()))
        ->toBe([
            0 => '/things/green-bike',
            $site->getId() => '/site/bike',
        ]);
});

it('leaves the slug empty when there is no default slug', function () {
    $object = SluggableFactory::createOne();

    expect(slugsBySite($object->getSlug()))
        ->toBe([]);
});

it('extends a slug that another object uses', function () {
    SluggableFactory::createOne([
        'name' => 'Green Bike',
    ]);

    $second = SluggableFactory::createOne([
        'name' => 'Green Bike',
    ]);
    $third = SluggableFactory::createOne([
        'slug' => [new UrlSlug('/things/green-bike', 0)],
    ]);

    expect(slugsBySite($second->getSlug()))
        ->toBe([0 => '/things/green-bike-1'])
        ->and(slugsBySite($third->getSlug()))
        ->toBe([0 => '/things/green-bike-2']);
});

it('keeps the own slug of an object that is saved again', function () {
    $object = SluggableFactory::createOne([
        'name' => 'Green Bike',
    ]);

    $object->setName('Red Bike');
    $object->save();

    expect(slugsBySite(Sluggable::getById($object->getId(), ['force' => true])->getSlug()))
        ->toBe([0 => '/things/green-bike']);
});

it('rejects a slug that another object uses when the field does not extend it', function () {
    SluggableFactory::createOne([
        'strictSlug' => [new UrlSlug('/taken', 0)],
    ]);

    SluggableFactory::createOne([
        'strictSlug' => [new UrlSlug('/taken', 0)],
    ]);
})->throws(Exception::class, 'Slug "/taken" is already used by object');
