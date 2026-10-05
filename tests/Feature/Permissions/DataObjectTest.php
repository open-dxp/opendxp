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

namespace OpenDxp\Tests\Feature\Permissions;

use OpenDxp\Model\DataObject;
use OpenDxp\Model\User;
use OpenDxp\Tests\Story\DataObjectPermissions as Tree;

const EVERY_TYPE = [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_VARIANT, DataObject::OBJECT_TYPE_FOLDER];
const WITH_ROLES_ONLY = 'Permissiontest4';
const ON_ONE_OBJECT = 'Permissiontest3';
const WITHOUT_ANY_MODULE = 'Permissiontest6';

/**
 * Reads the permissions OpenDXP calculated for an object and compares them with a table of
 * [path => [user => [permission => value]]].
 */
function assertObjectPermissions(array $expected): void
{
    foreach ($expected as $path => $perUser) {
        foreach ($perUser as $name => $types) {
            $calculated = DataObject::getByPath($path)->getUserPermissions(User::getByName($name));

            foreach ($types as $type => $value) {
                expect($calculated[$type])->toBe($value, sprintf('%s of %s for %s', $type, $path, $name));
            }
        }
    }
}

beforeEach(fn () => Tree::load());

it('tells per user whether a folder holds children that user may see', function () {

    $users = ['admin', 'Permissiontest1', 'Permissiontest2', ON_ONE_OBJECT, WITH_ROLES_ONLY];

    $expected = [
        Tree::ROOT . '/a' => [true, true, false, false, false],
        '/permissionfoo' => [true, true, true, true, true],
        '/permissionfoo/bars' => [true, true, true, true, true],
        '/permissionfoo/bars/hugo' => [false, false, false, false, false],
        '/permissionfoo/bars/userfolder' => [true, true, true, true, false],
        '/permissionfoo/bars/groupfolder' => [true, true, false, false, true],
        '/permissionfoo/bars/groupfolder/grouptestobject' => [false, false, false, false, false],
        '/permissionbar' => [true, false, false, false, false],
        '/permissionbar/foo' => [true, false, false, false, false],
        '/permissionbar/foo/hiddenobject' => [false, false, false, false, false],
    ];

    foreach ($expected as $path => $results) {
        foreach ($users as $index => $name) {
            expect(DataObject::getByPath($path)->getDao()->hasChildren(EVERY_TYPE, true, User::getByName($name)))
                ->toBe($results[$index], sprintf('children of %s for %s', $path, $name));
        }
    }
});

it('tells per user whether an object may be listed or viewed', function () {

    $expected = [
        '/permissionfoo' => ['list' => [true, true, true], 'view' => [true, true, true]],
        '/permissionfoo/bars' => ['list' => [true, true, true], 'view' => [true, false, false]],
        '/permissionfoo/bars/hugo' => ['list' => [true, false, false], 'view' => [true, false, false]],
        '/permissionfoo/bars/userfolder' => ['list' => [true, true, true], 'view' => [true, true, true]],
        '/permissionfoo/bars/groupfolder' => ['list' => [true, true, false], 'view' => [true, true, false]],
        '/permissionfoo/bars/groupfolder/grouptestobject' => ['list' => [true, true, false], 'view' => [true, true, false]],
        '/permissionbar' => ['list' => [true, true, true], 'view' => [true, true, true]],
        '/permissionbar/foo' => ['list' => [true, false, false], 'view' => [true, false, false]],
        '/permissionbar/foo/hiddenobject' => ['list' => [true, false, false], 'view' => [true, false, false]],
    ];

    foreach ($expected as $path => $perType) {
        foreach ($perType as $type => $results) {
            foreach (Tree::USERS as $index => $name) {
                expect(DataObject::getByPath($path)->isAllowed($type, User::getByName($name)))
                    ->toBe($results[$index], sprintf('%s of %s for %s', $type, $path, $name));
            }
        }
    }
});

it('hands a directly given permission down to the object below', function () {

    $onTheGroupFolder = [
        'admin' => ['save' => 1, 'delete' => 1, 'publish' => 1, 'settings' => 1, 'versions' => 1],
        'Permissiontest1' => ['save' => 1, 'delete' => 0, 'publish' => 0, 'settings' => 1, 'versions' => 0],
        'Permissiontest2' => ['save' => 1, 'delete' => 0, 'publish' => 1, 'settings' => 0, 'versions' => 0],
        ON_ONE_OBJECT => [],
        WITH_ROLES_ONLY => ['list' => 1, 'view' => 1],
        WITHOUT_ANY_MODULE => ['list' => 0, 'view' => 0, 'save' => 0, 'publish' => 0],
    ];
    $onTheUserFolder = [
        'admin' => ['view' => 1, 'delete' => 1, 'publish' => 1, 'versions' => 1, 'create' => 1, 'rename' => 1],
        'Permissiontest1' => ['view' => 1, 'delete' => 0, 'publish' => 0, 'versions' => 0, 'create' => 1, 'rename' => 1],
        'Permissiontest2' => ['view' => 1, 'delete' => 0, 'publish' => 0, 'versions' => 0, 'create' => 0, 'rename' => 0],
        WITHOUT_ANY_MODULE => ['list' => 0, 'view' => 0, 'save' => 0, 'publish' => 0],
    ];

    assertObjectPermissions([
        '/permissionfoo/bars/groupfolder' => $onTheGroupFolder,
        '/permissionfoo/bars/groupfolder/grouptestobject' => $onTheGroupFolder,
        '/permissionfoo/bars/userfolder' => $onTheUserFolder,
        '/permissionfoo/bars/userfolder/usertestobject' => $onTheUserFolder,
    ]);
});

it('lets a user list a folder above an object that user may see, although no rule names it', function () {

    $reachable = [
        'admin' => ['list' => 1, 'delete' => 1, 'publish' => 1, 'versions' => 1],
        'Permissiontest1' => ['list' => 1, 'delete' => 0, 'publish' => 0, 'versions' => 0],
        'Permissiontest2' => ['list' => 0, 'delete' => 0, 'publish' => 0, 'versions' => 0],
    ];

    assertObjectPermissions([
        Tree::ROOT . '/a' => $reachable,
        Tree::ROOT . '/a/b' => $reachable,
        Tree::ROOT . '/a/b/c' => $reachable,
    ]);
});
