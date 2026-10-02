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

use OpenDxp\Model\Asset;
use OpenDxp\Model\User;
use OpenDxp\Tests\Story\AssetPermissions;

/**
 * Reads the permissions OpenDXP calculated for an element and compares them with a table of
 * [path => [permission => one value per user]].
 */
function assertPermissions(array $expected): void
{
    foreach ($expected as $path => $perType) {
        foreach (AssetPermissions::USERS as $index => $name) {
            $calculated = Asset::getByPath($path)->getUserPermissions(User::getByName($name));

            foreach ($perType as $type => $results) {
                expect($calculated[$type])->toBe($results[$index], sprintf('%s of %s for %s', $type, $path, $name));
            }
        }
    }
}

beforeEach(fn () => AssetPermissions::load());

it('tells per user whether a folder holds children that user may see', function () {

    $expected = [
        '/permissioncpath/a' => [true, true, false],
        '/permissionfoo' => [true, true, true],
        '/permissionfoo/bars' => [true, true, true],
        '/permissionfoo/bars/hugo.gif' => [false, false, false],
        '/permissionfoo/bars/userfolder' => [true, true, true],
        '/permissionfoo/bars/groupfolder' => [true, true, false],
        '/permissionfoo/bars/groupfolder/grouptestobject.gif' => [false, false, false],
        '/permissionbar' => [true, false, false],
        '/permissionbar/foo' => [true, false, false],
        '/permissionbar/foo/hiddenobject.gif' => [false, false, false],
    ];

    foreach ($expected as $path => $results) {
        foreach (AssetPermissions::USERS as $index => $name) {
            expect(Asset::getByPath($path)->getDao()->hasChildren(User::getByName($name)))
                ->toBe($results[$index], sprintf('children of %s for %s', $path, $name));
        }
    }
});

it('tells per user whether an element may be listed or viewed', function () {

    $expected = [
        '/permissionfoo' => ['list' => [true, true, true], 'view' => [true, true, true]],
        '/permissionfoo/bars' => ['list' => [true, true, true], 'view' => [true, false, false]],
        '/permissionfoo/bars/hugo.gif' => ['list' => [true, false, false], 'view' => [true, false, false]],
        '/permissionfoo/bars/userfolder' => ['list' => [true, true, true], 'view' => [true, true, true]],
        '/permissionfoo/bars/groupfolder' => ['list' => [true, true, false], 'view' => [true, true, false]],
        '/permissionfoo/bars/groupfolder/grouptestobject.gif' => ['list' => [true, true, false], 'view' => [true, true, false]],
        '/permissionbar' => ['list' => [true, true, true], 'view' => [true, true, true]],
        '/permissionbar/foo' => ['list' => [true, false, false], 'view' => [true, false, false]],
        '/permissionbar/foo/hiddenobject.gif' => ['list' => [true, false, false], 'view' => [true, false, false]],
    ];

    foreach ($expected as $path => $perType) {
        foreach ($perType as $type => $results) {
            foreach (AssetPermissions::USERS as $index => $name) {
                expect(Asset::getByPath($path)->isAllowed($type, User::getByName($name)))
                    ->toBe($results[$index], sprintf('%s of %s for %s', $type, $path, $name));
            }
        }
    }
});

it('hands a directly given permission down to the element below', function () {
    assertPermissions([
        '/permissionfoo/bars/groupfolder' => ['delete' => [1, 0, 1], 'publish' => [1, 0, 1], 'versions' => [1, 0, 0]],
        '/permissionfoo/bars/groupfolder/grouptestobject.gif' => ['delete' => [1, 0, 1], 'publish' => [1, 0, 1], 'versions' => [1, 0, 0]],
        '/permissionfoo/bars/userfolder' => [
            'view' => [1, 1, 1], 'delete' => [1, 0, 0], 'publish' => [1, 0, 0],
            'versions' => [1, 0, 0], 'create' => [1, 1, 0], 'rename' => [1, 1, 0],
        ],
        '/permissionfoo/bars/userfolder/usertestobject.gif' => [
            'view' => [1, 1, 1], 'delete' => [1, 0, 0], 'publish' => [1, 0, 0],
            'versions' => [1, 0, 0], 'create' => [1, 1, 0], 'rename' => [1, 1, 0],
        ],
    ]);
});

it('lets a user list a folder above an element that user may see, although no rule names it', function () {
    assertPermissions([
        '/permissioncpath/a' => ['list' => [1, 1, 0], 'delete' => [1, 0, 0], 'publish' => [1, 0, 0], 'versions' => [1, 0, 0]],
        '/permissioncpath/a/b' => ['list' => [1, 1, 0], 'delete' => [1, 0, 0], 'publish' => [1, 0, 0], 'versions' => [1, 0, 0]],
        '/permissioncpath/a/b/c.gif' => ['list' => [1, 1, 0], 'delete' => [1, 0, 0], 'publish' => [1, 0, 0], 'versions' => [1, 0, 0]],
    ]);
});
