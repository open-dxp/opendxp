<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Element;

it('removes a deleted element from its folder', function (string $factory, string $folderFactory) {
    $folder = $folderFactory::createOne();
    $element = $factory::new()
        ->withParent($folder)
        ->create();

    $element->delete();

    expect($element::getById(
        $element->getId(),
        ['force' => true],
    ))
        ->toBeNull()
        ->and($folder->hasChildren())
        ->toBeFalse();
})->with('elements with folders');
