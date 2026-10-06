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

namespace OpenDxp\Tests\Feature\ClassificationStore;

use InvalidArgumentException;
use OpenDxp\Model\DataObject\Classificationstore\KeyConfig;
use OpenDxp\Model\DataObject\Classificationstore\StoreConfig;

function renamedKey(string $name): KeyConfig
{
    $key = storeKey('input');
    $definition = json_decode($key->getDefinition(), true);
    $key->setName($name);
    $key->setDefinition(json_encode([
        ...$definition,
        'name' => $name,
    ]));

    return $key;
}

it('saves a key whose definition names a valid field', function () {
    $key = renamedKey('color_code');

    $key->save();

    expect(KeyConfig::getByName('color_code', StoreConfig::getByName(TEST_STORE)->getId()))
        ->toBeInstanceOf(KeyConfig::class);
});

it('refuses to save a key whose definition names an invalid field', function (string $name) {
    $key = renamedKey($name);

    expect(fn () => $key->save())->toThrow(InvalidArgumentException::class, sprintf('Invalid field name "%s"', $name));
})->with([
    'a name with a dash' => ['color-code'],
    'a name that starts with a digit' => ['2nd_color'],
    'a name longer than 63 characters' => [str_repeat('a', 64)],
]);
