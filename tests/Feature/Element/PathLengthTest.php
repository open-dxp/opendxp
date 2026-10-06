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

use Exception;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Tests\Factory\TestObjectFactory;

// Two parents with keys of the greatest length make a path of 512 characters.
beforeEach(function () {
    $grandparent = DataObjectFolderFactory::createOne(['key' => str_repeat('a', 255)]);
    $this->parent = DataObjectFolderFactory::new()
        ->withParent($grandparent)
        ->create(['key' => str_repeat('b', 255)]);
});

it('saves an element with a path of 765 characters', function () {
    $object = TestObjectFactory::new()
        ->withParent($this->parent)
        ->create(['key' => str_repeat('c', 252)]);

    expect(reloaded($object)->getRealFullPath())->toHaveLength(765);
});

it('refuses an element with a path of more than 765 characters', function () {
    TestObjectFactory::new()
        ->withParent($this->parent)
        ->create(['key' => str_repeat('c', 253)]);
})->throws(Exception::class, "Full path is limited to 765 characters, reduce the length of your parent's path");
