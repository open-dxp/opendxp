<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Element;

use OpenDxp\Model\Element\Service;

it('finds an element under its new path once it was moved', function (string $factory, string $folderFactory) {
    $element = $factory::createOne();
    $folder = $folderFactory::createOne();
    $key = sprintf('%s-moved', $element->getKey());
    $newPath = sprintf('%s/%s', $folder->getRealFullPath(), $key);
    $element->setParentId($folder->getId());
    $element->setKey($key);

    $element->save();

    expect($element::getByPath($newPath)?->getId())->toBe($element->getId());
})->with('elements with folders');

it('knows the path of a saved element', function (string $element, string $factory) {
    $saved = $factory::createOne();

    $exists = Service::pathExists(
        $saved->getRealFullPath(),
        Service::getElementType($saved),
    );

    expect($exists)->toBeTrue();
})->with('elements');

it('knows a path that holds no element', function (string $type) {
    $exists = Service::pathExists('/nothing-is-saved-here', $type);

    expect($exists)->toBeFalse();
})->with([
    'an asset' => 'asset',
    'a document' => 'document',
    'an object' => 'object',
]);
