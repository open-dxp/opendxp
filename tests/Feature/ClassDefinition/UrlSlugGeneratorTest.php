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

namespace OpenDxp\Tests\Feature\ClassDefinition;

use Exception;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\ClassDefinition\Data\Localizedfields;
use OpenDxp\Model\DataObject\ClassDefinition\Data\UrlSlug;
use OpenDxp\Test\Factory\SiteFactory;
use OpenDxp\Tests\Application\Service\AvailableSitesListener;
use OpenDxp\Tests\Application\Service\FailingSlugGenerator;
use OpenDxp\Tests\Application\Service\ThingSlugGenerator;
use OpenDxp\Tests\Factory\SluggableFactory;
use stdClass;

function slugField(): UrlSlug
{
    $field = new UrlSlug();
    $field->setName('slug');

    return $field;
}

it('rejects a generator class that does not exist', function () {
    $field = slugField();
    $field->setSlugGeneratorClass('App\Missing\Generator');

    expect(fn () => $field->preSave(new ClassDefinition()))
        ->toThrow(Exception::class, 'Field slug: App\Missing\Generator is no slug generator.');
});

it('rejects a class that is no slug generator', function () {
    $field = slugField();
    $field->setSlugGeneratorClass(stdClass::class);

    expect(fn () => $field->preSave(new ClassDefinition()))
        ->toThrow(Exception::class, 'Field slug: stdClass is no slug generator.');
});

it('rejects a wrong generator inside localized fields', function () {
    $field = slugField();
    $field->setSlugGeneratorClass(stdClass::class);
    $localizedFields = new Localizedfields();
    $localizedFields->setName('localizedfields');
    $localizedFields->addChild($field);

    expect(fn () => $localizedFields->preSave(new ClassDefinition()))
        ->toThrow(Exception::class, 'Field slug: stdClass is no slug generator.');
});

it('rejects filling an empty slug without a generator', function () {
    $field = slugField();
    $field->setFillEmptySlug(true);

    expect(fn () => $field->preSave(new ClassDefinition()))
        ->toThrow(Exception::class, 'Field slug: Filling an empty slug needs a slug generator.');
});

it('gives the object editor the prefix of every language and site', function () {
    $site = SiteFactory::createOne();
    $object = SluggableFactory::createOne();
    $field = slugField();
    $field->setSlugGeneratorClass(ThingSlugGenerator::class);

    $field->enrichLayoutDefinition($object, ['ownerType' => 'localizedfield']);

    expect($field->getSlugPrefixes()['de'])
        ->toMatchArray([
            0 => '/de/things',
            $site->getId() => sprintf('/de/site-%d/things', $site->getId()),
        ]);
});

it('gives the object editor prefixes without a language outside of localized fields', function () {
    $object = SluggableFactory::createOne();
    $field = slugField();
    $field->setSlugGeneratorClass(ThingSlugGenerator::class);

    $field->enrichLayoutDefinition($object);

    expect($field->getSlugPrefixes()[''][0])->toBe('/things');
});

it('leaves a site out of a language when a listener takes it away', function () {
    $keptSite = SiteFactory::createOne();
    $removedSite = SiteFactory::createOne();
    $object = SluggableFactory::createOne();
    $field = slugField();
    $field->setSlugGeneratorClass(ThingSlugGenerator::class);
    $listener = (new AvailableSitesListener())
        ->removeSite($removedSite, 'de');

    runWithAvailableSitesListener(
        $listener,
        fn () => $field->enrichLayoutDefinition($object, ['ownerType' => 'localizedfield']),
    );

    expect($field->getSlugSites()['de'])
        ->toBe([$keptSite->getId()])
        ->and($field->getSlugPrefixes()['de'])
        ->not->toHaveKey($removedSite->getId())
        ->and($field->getSlugSites()['fr'])
        ->toBe([$keptSite->getId(), $removedSite->getId()]);
});

it('takes a language out of the object editor when a listener makes it unavailable', function () {
    $object = SluggableFactory::createOne();
    $field = slugField();
    $field->setSlugGeneratorClass(ThingSlugGenerator::class);
    $listener = (new AvailableSitesListener())
        ->makeUnavailable('de');

    runWithAvailableSitesListener(
        $listener,
        fn () => $field->enrichLayoutDefinition($object, ['ownerType' => 'localizedfield']),
    );

    expect($field->getSlugSites()['de'])
        ->toBeNull()
        ->and($field->getSlugPrefixes())
        ->not->toHaveKey('de')
        ->and($field->getSlugPrefixes()['en'][0])
        ->toBe('/en/things');
});

it('opens the object editor with the message of a generator that fails', function () {
    $object = SluggableFactory::createOne();
    $field = slugField();
    $field->setSlugGeneratorClass(FailingSlugGenerator::class);

    $field->enrichLayoutDefinition($object, ['ownerType' => 'localizedfield']);

    expect($field->getSlugErrors()['de'])
        ->toContain('The generator has no prefix.')
        ->and($field->getSlugPrefixes()['de'][0])
        ->toBeNull();
});

it('asks a listener for the next language after it failed for one', function () {
    $keptSite = SiteFactory::createOne();
    $removedSite = SiteFactory::createOne();
    $object = SluggableFactory::createOne();
    $field = slugField();
    $field->setSlugGeneratorClass(ThingSlugGenerator::class);
    $listener = (new AvailableSitesListener())
        ->failFor('de')
        ->removeSite($removedSite, 'fr');

    runWithAvailableSitesListener(
        $listener,
        fn () => $field->enrichLayoutDefinition($object, ['ownerType' => 'localizedfield']),
    );

    expect($field->getSlugErrors()['de'])
        ->toBe(['The listener fails for "de".'])
        ->and($field->getSlugSites()['fr'])
        ->toBe([$keptSite->getId()]);
});
