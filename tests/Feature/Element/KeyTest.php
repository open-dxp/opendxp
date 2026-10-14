<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\Element;

use Exception;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

it('refuses a key that is not valid', function (string $factory, string $type, string $name) {
    $unsaved = $factory::new()
        ->unsaved()
        ->create();
    $unsaved->setKey('a/b');

    expect(fn () => $unsaved->save())->toThrow(Exception::class, sprintf(
        'New %s "a/b" cannot be saved. The %s "a/b" is invalid.',
        $type,
        $name,
    ));
})->with([
    'an asset' => [AssetImageFactory::class, 'asset', 'filename'],
    'a document' => [DocumentPageFactory::class, 'document', 'key'],
    'an object' => [UnittestFactory::class, 'object', 'key'],
]);

it('refuses an empty key', function (string $factory, string $type) {
    $unsaved = $factory::new()
        ->unsaved()
        ->create();
    $unsaved->setKey('');

    expect(fn () => $unsaved->save())->toThrow(
        Exception::class,
        sprintf('New %s cannot be saved. Its key is empty.', $type),
    );
})->with([
    'a document' => [DocumentPageFactory::class, 'document'],
    'an object' => [UnittestFactory::class, 'object'],
]);

it('refuses a reserved key in the root folder', function () {
    $document = DocumentPageFactory::createOne();
    $document->setKey('admin');

    expect(fn () => $document->save())->toThrow(Exception::class, sprintf(
        'Document /admin (ID %d) cannot be saved. The key "admin" is reserved in the root folder.',
        $document->getId(),
    ));
});
