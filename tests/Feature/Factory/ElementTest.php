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

namespace OpenDxp\Tests\Feature\Factory;

use OpenDxp\Model\Asset;
use OpenDxp\Model\Asset\Image;
use OpenDxp\Model\DataObject\Folder;
use OpenDxp\Model\Element\Note;
use OpenDxp\Model\Element\Tag;
use OpenDxp\Model\Schedule\Task;
use OpenDxp\Test\Elements;
use OpenDxp\Test\Factory\AssetArchiveFactory;
use OpenDxp\Test\Factory\AssetAudioFactory;
use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\AssetTextFactory;
use OpenDxp\Test\Factory\AssetUnknownFactory;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\TagFactory;
use OpenDxp\Tests\Factory\TestFieldTypesFactory;

dataset('element types', [
    'an asset' => [AssetImageFactory::class, 'asset'],
    'a document' => [DocumentPageFactory::class, 'document'],
    'an object' => [TestFieldTypesFactory::class, 'object'],
]);

it('writes an image asset with a file behind it', function () {
    $image = AssetImageFactory::createOne();

    expect(Elements::refresh($image))
        ->toBeInstanceOf(Image::class)
        ->getFileSize()
        ->toBeGreaterThan(0);
});

it('puts an asset into a folder', function () {
    $folder = AssetFolderFactory::createOne(['filename' => 'photos']);

    $image = AssetImageFactory::new()
        ->withParent($folder)
        ->create();

    expect($image->getFullPath())->toStartWith('/photos/');
});

it('writes an object folder', function () {
    $folder = DataObjectFolderFactory::createOne(['key' => 'products']);

    expect(Elements::refresh($folder))
        ->toBeInstanceOf(Folder::class)
        ->getFullPath()
        ->toBe('/products');
});

it('writes an asset of the type its file has', function (string $factory, string $class) {
    $asset = $factory::createOne();

    expect(Elements::refresh($asset))->toBeInstanceOf($class);
})->with([
    'a text' => [AssetTextFactory::class, Asset\Text::class],
    'an audio file' => [AssetAudioFactory::class, Asset\Audio::class],
    'an archive' => [AssetArchiveFactory::class, Asset\Archive::class],
    'an unknown file' => [AssetUnknownFactory::class, Asset\Unknown::class],
]);

it('tags an element', function (string $factory, string $type) {
    $news = TagFactory::createOne(['name' => 'news']);
    $sale = TagFactory::createOne(['name' => 'sale']);

    $element = $factory::new()
        ->withTags($news, $sale)
        ->create();

    $names = array_map(
        static fn (Tag $tag): string => $tag->getName(),
        Tag::getTagsForElement($type, $element->getId()),
    );
    expect($names)->toBe([
        'news',
        'sale',
    ]);
})->with('element types');

it('writes a note about an element', function (string $factory, string $type) {
    $note = new Note();
    $note->setType('comment');
    $note->setTitle('Checked by legal');
    $note->setDescription('The terms are fine.');
    $note->setDate(1_767_225_600);
    $note->addData('approved', 'bool', true);

    $element = $factory::new()
        ->withNote($note)
        ->create();

    $listing = new Note\Listing();
    $listing->setCondition('cid = ? AND ctype = ?', [$element->getId(), $type]);
    $notes = $listing->load();
    expect($notes)
        ->toHaveCount(1)
        ->and($notes[0])
        ->getTitle()
        ->toBe('Checked by legal')
        ->getDescription()
        ->toBe('The terms are fine.')
        ->getDate()
        ->toBe(1_767_225_600)
        ->getData()
        ->toBe([
            'approved' => [
                'data' => true,
                'type' => 'bool',
            ],
        ]);
})->with('element types');

it('schedules a task for an element', function (string $factory) {
    $task = new Task([
        'action' => 'delete',
        'date' => 1_893_456_000,
        'active' => true,
    ]);

    $element = $factory::new()
        ->withScheduledTask($task)
        ->create();

    $tasks = Elements::refresh($element)->getScheduledTasks();
    expect($tasks)
        ->toHaveCount(1)
        ->and($tasks[0])
        ->getAction()
        ->toBe('delete')
        ->getDate()
        ->toBe(1_893_456_000)
        ->getActive()
        ->toBeTrue();
})->with('element types');
