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

namespace OpenDxp\Tests\Feature\DataObject;

use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

beforeEach(function () {
    $this->parent = UnittestFactory::createOne();
    $this->published = UnittestFactory::createOne(['parentId' => $this->parent->getId()]);
    $this->unpublished = UnittestFactory::new()->unpublished()->create(['parentId' => $this->parent->getId()]);
});

it('counts only the published children', function () {
    expect($this->parent->getChildren()->load())
        ->toHaveCount(1)
        ->and($this->parent->hasChildren())
        ->toBeTrue();
});

it('counts the unpublished children when it is asked to', function () {
    expect($this->parent->getChildren([], true)->load())
        ->toHaveCount(2)
        ->and($this->parent->hasChildren([], true))
        ->toBeTrue();
});

it('has no child at all when the only one is unpublished', function () {

    $folder = DataObjectFolderFactory::createOne();
    UnittestFactory::new()->unpublished()->create(['parentId' => $folder->getId()]);

    expect($folder->getChildren()->load())
        ->toHaveCount(0)
        ->and($folder->hasChildren())
        ->toBeFalse();
});

it('counts only the published siblings', function () {
    expect($this->published->getSiblings()->load())
        ->toHaveCount(0)
        ->and($this->published->hasSiblings())
        ->toBeFalse();
});

it('counts the unpublished siblings when it is asked to', function () {
    expect($this->published->getSiblings([], true)->load())
        ->toHaveCount(1)
        ->and($this->published->hasSiblings([], true))
        ->toBeTrue();
});
