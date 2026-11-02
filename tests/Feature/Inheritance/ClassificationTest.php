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

namespace OpenDxp\Tests\Feature\Inheritance;

use OpenDxp\Test\Elements;
use OpenDxp\Test\Factory\TestProductFactory;

beforeEach(function () {
    $this->group = storeGroup('values');
    $parent = TestProductFactory::new()
        ->withClassificationValues('specifications', $this->group, [
            'input' => 'input of the parent',
            'textarea' => 'textarea of the parent',
        ])
        ->create();
    $this->child = TestProductFactory::new()
        ->withParent($parent)
        ->withClassificationValues('specifications', $this->group, ['input' => 'input of the child'])
        ->create();
});

it('gives the child the value of its parent for a key it holds no value for', function () {
    $store = Elements::refresh($this->child)->getSpecifications();

    $value = $store->getLocalizedKeyValue(
        $this->group->getId(),
        storeKey('textarea')->getId(),
    );

    expect($value)->toBe('textarea of the parent');
});

it('keeps the value of the child for a key it holds its own value for', function () {
    $store = Elements::refresh($this->child)->getSpecifications();

    $value = $store->getLocalizedKeyValue(
        $this->group->getId(),
        storeKey('input')->getId(),
    );

    expect($value)->toBe('input of the child');
});
