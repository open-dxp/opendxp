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


namespace OpenDxp\Tests\Feature\Tool;

use OpenDxp\Db;
use OpenDxp\Model\Tool\SettingsStore;

function storedRow(string $id, ?string $scope): string|false
{
    return Db::get()->fetchOne(
        sprintf('SELECT id FROM %s WHERE id = :id AND scope = :scope', SettingsStore\Dao::TABLE_NAME),
        ['id' => $id, 'scope' => (string) $scope],
    );
}

dataset('settings', [
    'a string without a scope' => ['this is a string', null, SettingsStore::TYPE_STRING, 'string'],
    'a string in a scope' => ['this is another string', 'my-scope', SettingsStore::TYPE_STRING, 'string'],
    'an integer without a scope' => [123, null, SettingsStore::TYPE_INTEGER, 'int'],
    'an integer in a scope' => [321, 'my-scope', SettingsStore::TYPE_INTEGER, 'int'],
    'a boolean without a scope' => [true, null, SettingsStore::TYPE_BOOLEAN, 'bool'],
    'a boolean in a scope' => [false, 'my-scope', SettingsStore::TYPE_BOOLEAN, 'bool'],
    'a float without a scope' => [2154.12, null, SettingsStore::TYPE_FLOAT, 'float'],
    'a float in a scope' => [2541.1247, 'my-scope', SettingsStore::TYPE_FLOAT, 'float'],
]);

it('hands a value back with the type it was stored as', function (mixed $value, ?string $scope, string $type, string $phpType) {

    SettingsStore::set('my-id', $value, $type, $scope);

    $setting = SettingsStore::get('my-id', $scope);

    expect($setting->getData())
        ->toBe($value)
        ->and(get_debug_type($setting->getData()))
        ->toBe($phpType)
        ->and($setting->getScope())
        ->toBe((string) $scope);
})->with('settings');

it('keeps a row of its own for the value', function (mixed $value, ?string $scope, string $type) {

    SettingsStore::set('my-id', $value, $type, $scope);

    expect(storedRow('my-id', $scope))->toBe('my-id');
})->with('settings');

it('replaces a value that is set a second time', function (mixed $value, ?string $scope, string $type) {

    SettingsStore::set('my-id', $value, $type, $scope);
    SettingsStore::set('my-id', 'updated_data', SettingsStore::TYPE_STRING, $scope);

    expect(SettingsStore::get('my-id', $scope)->getData())->toBe('updated_data');
})->with('settings');

it('leaves no row behind once the value was deleted', function (mixed $value, ?string $scope, string $type) {

    SettingsStore::set('my-id', $value, $type, $scope);
    SettingsStore::delete('my-id', $scope);

    expect(storedRow('my-id', $scope))->toBeFalse();
})->with('settings');

it('lists the ids of one scope and no others', function () {

    SettingsStore::set('my-id1', 'some-data-1-scopeless', SettingsStore::TYPE_STRING);
    SettingsStore::set('my-id1', 'some-data-1', SettingsStore::TYPE_STRING, 'scope1');
    SettingsStore::set('my-id2', 'some-data-2', SettingsStore::TYPE_STRING, 'scope1');
    SettingsStore::set('my-id3', 'some-data-3', SettingsStore::TYPE_STRING, 'scope2');
    SettingsStore::set('my-id4', 'some-data-4', SettingsStore::TYPE_STRING, 'scope1');

    expect(SettingsStore::getIdsByScope('scope1'))
        ->toHaveCount(3)
        ->toContain('my-id1')
        ->not->toContain('my-id3')
        ->and(SettingsStore::getIdsByScope('scopeX'))
        ->toBeEmpty();
});

it('keeps the same id apart per scope', function () {

    SettingsStore::set('my-id1', 'some-data-1-scopeless', SettingsStore::TYPE_STRING);
    SettingsStore::set('my-id1', 'some-data-1', SettingsStore::TYPE_STRING, 'scope1');
    SettingsStore::set('my-id1', 'some-data-1-scope-2', SettingsStore::TYPE_STRING, 'scope2');

    expect(SettingsStore::get('my-id1')->getData())
        ->toBe('some-data-1-scopeless')
        ->and(SettingsStore::get('my-id1', 'scope1')->getData())
        ->toBe('some-data-1')
        ->and(SettingsStore::get('my-id1', 'scope2')->getData())
        ->toBe('some-data-1-scope-2');
});

it('leaves the other scopes alone when one id is deleted', function () {

    SettingsStore::set('my-id1', 'some-data-1-scopeless', SettingsStore::TYPE_STRING);
    SettingsStore::set('my-id1', 'some-data-1', SettingsStore::TYPE_STRING, 'scope1');

    SettingsStore::delete('my-id1');

    expect(SettingsStore::get('my-id1'))
        ->toBeNull()
        ->and(SettingsStore::get('my-id1', 'scope1')->getData())
        ->toBe('some-data-1');
});

it('hands back nothing for an id it does not know', function () {
    expect(SettingsStore::get('my-id1x'))->toBeNull();
});
