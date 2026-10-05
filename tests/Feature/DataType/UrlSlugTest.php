<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\DataType;

use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Test\Factory\SiteFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

it('keeps the slug of every site after the object is saved', function () {
    $firstSite = SiteFactory::createOne();
    $secondSite = SiteFactory::createOne();
    $object = UnittestFactory::createOne();

    $object->setUrlSlug($object->getClass()->getFieldDefinition('urlSlug')->getDataFromEditmode([
        [0, '/fallback', ''],
        [$firstSite->getId(), '/first', ''],
        [$secondSite->getId(), '/second', ''],
    ], $object));
    $object->save();

    expect(slugsBySite(Unittest::getById($object->getId(), ['force' => true])->getUrlSlug()))
        ->toBe([
            0 => '/fallback',
            $firstSite->getId() => '/first',
            $secondSite->getId() => '/second',
        ]);
});
