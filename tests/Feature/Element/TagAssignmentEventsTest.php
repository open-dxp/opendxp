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

namespace OpenDxp\Tests\Feature\Element;

use OpenDxp;
use OpenDxp\Event\Model\TagEvent;
use OpenDxp\Event\TagEvents;
use OpenDxp\Model\Element\Tag;
use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\TagFactory;

/**
 * @return list<string> one entry per event, as "event element tag"
 */
function tagEventsDispatchedBy(callable $assign): array
{
    $dispatched = [];
    $dispatcher = OpenDxp::getEventDispatcher();
    $listeners = [];

    foreach ([
        TagEvents::PRE_ADD_TO_ELEMENT => 'pre add',
        TagEvents::POST_ADD_TO_ELEMENT => 'post add',
        TagEvents::PRE_REMOVE_FROM_ELEMENT => 'pre remove',
        TagEvents::POST_REMOVE_FROM_ELEMENT => 'post remove',
    ] as $eventName => $label) {
        $listeners[$eventName] = static function (TagEvent $event) use (&$dispatched, $label): void {
            $dispatched[] = sprintf('%s %d %s', $label, $event->getArgument('elementId'), $event->getTag()->getName());
        };
        $dispatcher->addListener($eventName, $listeners[$eventName]);
    }

    try {
        $assign();
    } finally {
        foreach ($listeners as $eventName => $listener) {
            $dispatcher->removeListener($eventName, $listener);
        }
    }

    return $dispatched;
}

it('dispatches the events of a batch assignment only for the tags that change, element by element', function () {
    $first = AssetFolderFactory::new()->create();
    $second = AssetFolderFactory::new()->create();
    $kept = TagFactory::new()->with(['name' => 'kept'])->assignedTo($first)->create();
    $dropped = TagFactory::new()->with(['name' => 'dropped'])->assignedTo($first)->create();
    $added = TagFactory::new()->with(['name' => 'added'])->create();

    $events = tagEventsDispatchedBy(fn () => Tag::batchAssignTagsToElement(
        'asset',
        [$first->getId(), $second->getId(), $first->getId()],
        [$kept->getId(), $added->getId(), $added->getId()],
        true,
    ));

    expect($events)->toBe([
        "pre remove {$first->getId()} dropped",
        "pre add {$first->getId()} added",
        "pre add {$second->getId()} kept",
        "pre add {$second->getId()} added",
        "post remove {$first->getId()} dropped",
        "post add {$first->getId()} added",
        "post add {$second->getId()} kept",
        "post add {$second->getId()} added",
    ]);
});

it('keeps the other tags of the elements when a batch assignment does not replace them', function () {
    $folder = AssetFolderFactory::new()->create();
    TagFactory::new()->with(['name' => 'kept'])->assignedTo($folder)->create();
    $added = TagFactory::new()->with(['name' => 'added'])->create();

    $events = tagEventsDispatchedBy(fn () => Tag::batchAssignTagsToElement('asset', [$folder->getId()], [$added->getId()]));

    expect($events)->toBe([
        "pre add {$folder->getId()} added",
        "post add {$folder->getId()} added",
    ]);
});

it('dispatches the events of the tags that setting the tags of an element adds and removes', function () {
    $folder = AssetFolderFactory::new()->create();
    $kept = TagFactory::new()->with(['name' => 'kept'])->assignedTo($folder)->create();
    TagFactory::new()->with(['name' => 'dropped'])->assignedTo($folder)->create();
    $added = TagFactory::new()->with(['name' => 'added'])->create();

    $events = tagEventsDispatchedBy(fn () => Tag::setTagsForElement('asset', $folder->getId(), [$kept, $added]));

    expect($events)->toBe([
        "pre remove {$folder->getId()} dropped",
        "pre add {$folder->getId()} added",
        "post remove {$folder->getId()} dropped",
        "post add {$folder->getId()} added",
    ])
        ->and(array_map(fn (Tag $tag) => $tag->getName(), Tag::getTagsForElement('asset', $folder->getId())))
        ->toBe(['added', 'kept']);
});

it('dispatches the post events after the assignments are written', function () {
    $folder = AssetFolderFactory::new()->create();
    $tag = TagFactory::new()->with(['name' => 'added'])->create();
    $tagsInListener = [];
    $listener = static function (TagEvent $event) use (&$tagsInListener): void {
        $tagsInListener = Tag::getTagsForElement('asset', $event->getArgument('elementId'));
    };
    OpenDxp::getEventDispatcher()->addListener(TagEvents::POST_ADD_TO_ELEMENT, $listener);

    try {
        Tag::setTagsForElement('asset', $folder->getId(), [$tag]);
    } finally {
        OpenDxp::getEventDispatcher()->removeListener(TagEvents::POST_ADD_TO_ELEMENT, $listener);
    }

    expect($tagsInListener)->toHaveCount(1);
});

it('dispatches no event when the tags of an element stay the same', function () {
    $folder = AssetFolderFactory::new()->create();
    $tag = TagFactory::new()->assignedTo($folder)->create();

    expect(tagEventsDispatchedBy(fn () => Tag::setTagsForElement('asset', $folder->getId(), [$tag])))->toBe([]);
});
