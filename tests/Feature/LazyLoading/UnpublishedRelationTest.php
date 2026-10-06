<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\LazyLoading;

use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Tests\Factory\LazyLoadingFactory;
use OpenDxp\Tests\Factory\RelationTestFactory;

beforeEach(function () {
    $unpublished = RelationTestFactory::new()
        ->unpublished()
        ->create();
    $this->object = LazyLoadingFactory::createOne(['relations' => [$unpublished]]);
});

afterEach(fn () => Concrete::setHideUnpublished(true));

it('returns no relation to an unpublished target while unpublished objects are hidden', function () {
    $loaded = reloaded($this->object);

    expect($loaded->getRelations())->toBeEmpty();
});

it('returns the relation to an unpublished target once unpublished objects are shown', function () {
    $loaded = reloaded($this->object);

    Concrete::setHideUnpublished(false);

    expect($loaded->getRelations())->toHaveCount(1);
});

it('drops the relation to an unpublished target once the field is emptied', function () {
    Concrete::setHideUnpublished(false);

    $this->object->setRelations([]);
    $this->object->save();

    expect(reloaded($this->object)->getRelations())->toBeEmpty();
});
