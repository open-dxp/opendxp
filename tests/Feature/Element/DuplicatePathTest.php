<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Element;

use OpenDxp\Model\Element\DuplicateFullPathException;

it('refuses a path that another element holds', function (string $factory, string $type) {
    $holder = $factory::createOne();
    $unsaved = $factory::new()
        ->unsaved()
        ->create();
    $unsaved->setKey($holder->getKey());

    expect(fn () => $unsaved->save())->toThrow(DuplicateFullPathException::class, sprintf(
        'New %s "%s" cannot be saved. The path %s is already taken by %s %d.',
        $type,
        $holder->getKey(),
        $holder->getRealFullPath(),
        $type,
        $holder->getId(),
    ));
})->with('element types');
