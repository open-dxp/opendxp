<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Factory;

use OpenDxp\Model\Metadata;
use OpenDxp\Model\Property;
use OpenDxp\Test\Factory\PredefinedAssetMetadataFactory;
use OpenDxp\Test\Factory\PredefinedPropertyFactory;

it('writes a predefined property', function () {
    PredefinedPropertyFactory::createOne(['key' => 'teaser_color']);

    expect(Property\Predefined::getByKey('teaser_color'))
        ->getType()
        ->toBe('text')
        ->getCtype()
        ->toBe('document');
});

it('writes predefined asset metadata', function () {
    PredefinedAssetMetadataFactory::createOne(['name' => 'copyright']);

    expect(Metadata\Predefined::getByName('copyright'))->getType()->toBe('input');
});
