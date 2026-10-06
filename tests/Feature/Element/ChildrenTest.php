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

use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

dataset('publishable elements', [
    'a document' => DocumentPageFactory::class,
    'an object' => UnittestFactory::class,
]);

it('lists only the published children', function (string $factory) {
    $parent = $factory::createOne();
    $factory::new()
        ->withParent($parent)
        ->create();
    $factory::new()
        ->withParent($parent)
        ->unpublished()
        ->create();

    $children = $parent->getChildren();

    expect($children)->toHaveCount(1);
})->with('publishable elements');

it('lists the unpublished children on request', function (string $factory) {
    $parent = $factory::createOne();
    $factory::new()
        ->withParent($parent)
        ->create();
    $factory::new()
        ->withParent($parent)
        ->unpublished()
        ->create();

    $children = $parent->getChildren(includingUnpublished: true);

    expect($children)->toHaveCount(2);
})->with('publishable elements');

it('has children when one of them is published', function (string $factory) {
    $parent = $factory::createOne();
    $factory::new()
        ->withParent($parent)
        ->create();

    $hasChildren = $parent->hasChildren();

    expect($hasChildren)->toBeTrue();
})->with('publishable elements');

it('has no children when its only child is unpublished', function (string $factory) {
    $parent = $factory::createOne();
    $factory::new()
        ->withParent($parent)
        ->unpublished()
        ->create();

    $hasChildren = $parent->hasChildren();

    expect($hasChildren)->toBeFalse();
})->with('publishable elements');

it('has children on request when its only child is unpublished', function (string $factory) {
    $parent = $factory::createOne();
    $factory::new()
        ->withParent($parent)
        ->unpublished()
        ->create();

    $hasChildren = $parent->hasChildren(includingUnpublished: true);

    expect($hasChildren)->toBeTrue();
})->with('publishable elements');

it('lists only the published siblings', function (string $factory) {
    $parent = $factory::createOne();
    $child = $factory::new()
        ->withParent($parent)
        ->create();
    $factory::new()
        ->withParent($parent)
        ->unpublished()
        ->create();

    $siblings = $child->getSiblings();

    expect($siblings)->toHaveCount(0);
})->with('publishable elements');

it('lists the unpublished siblings on request', function (string $factory) {
    $parent = $factory::createOne();
    $child = $factory::new()
        ->withParent($parent)
        ->create();
    $factory::new()
        ->withParent($parent)
        ->unpublished()
        ->create();

    $siblings = $child->getSiblings(includingUnpublished: true);

    expect($siblings)->toHaveCount(1);
})->with('publishable elements');

it('has no siblings when its only sibling is unpublished', function (string $factory) {
    $parent = $factory::createOne();
    $child = $factory::new()
        ->withParent($parent)
        ->create();
    $factory::new()
        ->withParent($parent)
        ->unpublished()
        ->create();

    $hasSiblings = $child->hasSiblings();

    expect($hasSiblings)->toBeFalse();
})->with('publishable elements');

it('has siblings on request when its only sibling is unpublished', function (string $factory) {
    $parent = $factory::createOne();
    $child = $factory::new()
        ->withParent($parent)
        ->create();
    $factory::new()
        ->withParent($parent)
        ->unpublished()
        ->create();

    $hasSiblings = $child->hasSiblings(includingUnpublished: true);

    expect($hasSiblings)->toBeTrue();
})->with('publishable elements');
