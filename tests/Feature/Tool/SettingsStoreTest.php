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

function storedId(string $id, string $storedScope): string|false
{
    $query = sprintf('SELECT id FROM %s WHERE id = :id AND scope = :scope', SettingsStore\Dao::TABLE_NAME);

    return Db::get()->fetchOne($query, [
        'id' => $id,
        'scope' => $storedScope,
    ]);
}

dataset('values', [
    'a string' => ['this is a string', SettingsStore::TYPE_STRING],
    'an integer' => [123, SettingsStore::TYPE_INTEGER],
    'a boolean' => [true, SettingsStore::TYPE_BOOLEAN],
    'a float' => [2154.12, SettingsStore::TYPE_FLOAT],
]);

// The store keeps a value without a scope under an empty scope.
dataset('scopes', [
    'without a scope' => [null, ''],
    'in a scope' => ['my-scope', 'my-scope'],
]);

it('returns a value with the type and the scope it was stored with', function (
    mixed $value,
    string $type,
    ?string $scope,
    string $storedScope,
) {
    SettingsStore::set('my-id', $value, $type, $scope);

    $setting = SettingsStore::get('my-id', $scope);

    expect($setting)
        ->getData()
        ->toBe($value)
        ->getScope()
        ->toBe($storedScope);
})->with('values')->with('scopes');

it('keeps a row of its own for the value', function (
    mixed $value,
    string $type,
    ?string $scope,
    string $storedScope,
) {
    SettingsStore::set('my-id', $value, $type, $scope);

    expect(storedId('my-id', $storedScope))->toBe('my-id');
})->with('values')->with('scopes');

it('replaces a value that is set a second time', function (mixed $value, string $type, ?string $scope) {
    SettingsStore::set('my-id', $value, $type, $scope);

    SettingsStore::set('my-id', 'updated_data', SettingsStore::TYPE_STRING, $scope);

    expect(SettingsStore::get('my-id', $scope))
        ->getData()
        ->toBe('updated_data');
})->with('values')->with('scopes');

it('leaves no row behind once the value was deleted', function (
    mixed $value,
    string $type,
    ?string $scope,
    string $storedScope,
) {
    SettingsStore::set('my-id', $value, $type, $scope);

    SettingsStore::delete('my-id', $scope);

    expect(storedId('my-id', $storedScope))->toBeFalse();
})->with('values')->with('scopes');

it('lists the ids of one scope and no others', function () {
    SettingsStore::set('my-id1', 'some-data-1-scopeless', SettingsStore::TYPE_STRING);
    SettingsStore::set('my-id1', 'some-data-1', SettingsStore::TYPE_STRING, 'scope1');
    SettingsStore::set('my-id2', 'some-data-2', SettingsStore::TYPE_STRING, 'scope1');
    SettingsStore::set('my-id3', 'some-data-3', SettingsStore::TYPE_STRING, 'scope2');
    SettingsStore::set('my-id4', 'some-data-4', SettingsStore::TYPE_STRING, 'scope1');

    $ids = SettingsStore::getIdsByScope('scope1');

    expect($ids)->toEqualCanonicalizing([
        'my-id1',
        'my-id2',
        'my-id4',
    ]);
});

it('lists no ids for a scope without values', function () {
    SettingsStore::set('my-id1', 'some-data-1', SettingsStore::TYPE_STRING, 'scope1');

    $ids = SettingsStore::getIdsByScope('scopeX');

    expect($ids)->toBeEmpty();
});

it('keeps the same id apart per scope', function (?string $scope, string $expected) {
    SettingsStore::set('my-id1', 'some-data-1-scopeless', SettingsStore::TYPE_STRING);
    SettingsStore::set('my-id1', 'some-data-1', SettingsStore::TYPE_STRING, 'scope1');
    SettingsStore::set('my-id1', 'some-data-1-scope-2', SettingsStore::TYPE_STRING, 'scope2');

    $setting = SettingsStore::get('my-id1', $scope);

    expect($setting)
        ->getData()
        ->toBe($expected);
})->with([
    'without a scope' => [null, 'some-data-1-scopeless'],
    'in the first scope' => ['scope1', 'some-data-1'],
    'in the second scope' => ['scope2', 'some-data-1-scope-2'],
]);

it('leaves the other scopes alone when one id is deleted', function () {
    SettingsStore::set('my-id1', 'some-data-1-scopeless', SettingsStore::TYPE_STRING);
    SettingsStore::set('my-id1', 'some-data-1', SettingsStore::TYPE_STRING, 'scope1');

    SettingsStore::delete('my-id1');

    expect(SettingsStore::get('my-id1'))
        ->toBeNull()
        ->and(SettingsStore::get('my-id1', 'scope1'))
        ->getData()
        ->toBe('some-data-1');
});

it('returns null for an id it does not know', function () {
    $setting = SettingsStore::get('my-id1x');

    expect($setting)->toBeNull();
});
