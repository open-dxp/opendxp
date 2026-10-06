<?php

declare(strict_types=1);

use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Test\Factory\DocumentFolderFactory;
use OpenDxp\Test\Factory\DocumentHardlinkFactory;
use OpenDxp\Test\Factory\DocumentLinkFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\TestObjectFactory;

dataset('sources', [
    'a page' => [fn () => DocumentPageFactory::createOne()],
    'a document link' => [fn () => DocumentLinkFactory::createOne()],
    'a hardlink' => [
        fn () => DocumentHardlinkFactory::new()
            ->withSource(DocumentPageFactory::createOne())
            ->create(),
    ],
    'a document folder' => [fn () => DocumentFolderFactory::createOne()],
    'an image' => [fn () => AssetImageFactory::createOne()],
    'an asset folder' => [fn () => AssetFolderFactory::createOne()],
    'an object' => [fn () => TestObjectFactory::createOne()],
    'an object folder' => [fn () => DataObjectFolderFactory::createOne()],
]);
