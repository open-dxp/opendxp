<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\ClassDefinition;

use Exception;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Test\Factory\SiteFactory;
use OpenDxp\Tests\Application\Service\ThingSlugGenerator;
use OpenDxp\Tests\Factory\SluggableFactory;
use stdClass;

it('rejects a generator class that does not exist', function () {
    slugField('App\Missing\Generator')->preSave(new ClassDefinition());
})->throws(Exception::class, 'Field slug: App\Missing\Generator is no slug generator.');

it('rejects a class that is no slug generator', function () {
    slugField(stdClass::class)->preSave(new ClassDefinition());
})->throws(Exception::class, 'Field slug: stdClass is no slug generator.');

it('rejects a wrong generator inside localized fields', function () {
    localizedFieldsWith(slugField(stdClass::class))->preSave(new ClassDefinition());
})->throws(Exception::class, 'Field slug: stdClass is no slug generator.');

it('rejects filling an empty slug without a generator', function () {
    slugField(null)
        ->setFillEmptySlug(true)
        ->preSave(new ClassDefinition());
})->throws(Exception::class, 'Field slug: Filling an empty slug needs a slug generator.');

it('hands the object editor the prefix of every language and site', function () {
    $site = SiteFactory::createOne();
    $field = slugField(ThingSlugGenerator::class);

    $field->enrichLayoutDefinition(SluggableFactory::createOne(), [
        'ownerType' => 'localizedfield',
    ]);

    expect($field->getSlugPrefixes()['de'])
        ->toMatchArray([
            0 => '/de/things',
            $site->getId() => sprintf('/de/site-%d/things', $site->getId()),
        ]);
});

it('hands the object editor prefixes without a language outside of localized fields', function () {
    $field = slugField(ThingSlugGenerator::class);

    $field->enrichLayoutDefinition(SluggableFactory::createOne());

    expect($field->getSlugPrefixes()[''][0])
        ->toBe('/things');
});
