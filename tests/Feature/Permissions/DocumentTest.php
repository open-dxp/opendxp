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

use OpenDxp\Model\Document;
use OpenDxp\Model\User;
use OpenDxp\Tests\Story\DocumentPermissions;

/**
 * Reads the permissions OpenDXP calculated for a document and compares them with a table of
 * [path => [permission => one value per user]].
 */
function assertDocumentPermissions(array $expected): void
{
    foreach ($expected as $path => $perType) {
        foreach (DocumentPermissions::USERS as $index => $name) {
            $calculated = Document::getByPath($path)->getUserPermissions(User::getByName($name));

            foreach ($perType as $type => $results) {
                expect($calculated[$type])->toBe($results[$index], sprintf('%s of %s for %s', $type, $path, $name));
            }
        }
    }
}

beforeEach(fn () => DocumentPermissions::load());

it('tells per user whether a folder holds children that user may see', function () {

    $expected = [
        '/permissioncpath/a' => [true, true, false],
        '/permissionfoo' => [true, true, true],
        '/permissionfoo/bars' => [true, true, true],
        '/permissionfoo/bars/hugo' => [false, false, false],
        '/permissionfoo/bars/userfolder' => [true, true, true],
        '/permissionfoo/bars/groupfolder' => [true, true, false],
        '/permissionfoo/bars/groupfolder/grouptestobject' => [false, false, false],
        '/permissionbar' => [true, false, false],
        '/permissionbar/foo' => [true, false, false],
        '/permissionbar/foo/hiddenobject' => [false, false, false],
    ];

    foreach ($expected as $path => $results) {
        foreach (DocumentPermissions::USERS as $index => $name) {
            expect(Document::getByPath($path)->getDao()->hasChildren(true, User::getByName($name)))
                ->toBe($results[$index], sprintf('children of %s for %s', $path, $name));
        }
    }
});

it('tells per user whether a document may be listed or viewed', function () {

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
            foreach (DocumentPermissions::USERS as $index => $name) {
                expect(Document::getByPath($path)->isAllowed($type, User::getByName($name)))
                    ->toBe($results[$index], sprintf('%s of %s for %s', $type, $path, $name));
            }
        }
    }
});

it('hands a directly given permission down to the document below', function () {

    $onTheGroupFolder = [
        'save' => [1, 1, 1], 'delete' => [1, 0, 0], 'publish' => [1, 0, 1],
        'unpublish' => [1, 1, 0], 'versions' => [1, 0, 0],
    ];
    $onTheUserFolder = [
        'view' => [1, 1, 1], 'delete' => [1, 0, 0], 'publish' => [1, 0, 0],
        'versions' => [1, 0, 0], 'create' => [1, 1, 0], 'rename' => [1, 1, 0],
    ];

    assertDocumentPermissions([
        '/permissionfoo/bars/groupfolder' => $onTheGroupFolder,
        '/permissionfoo/bars/groupfolder/grouptestobject' => $onTheGroupFolder,
        '/permissionfoo/bars/userfolder' => $onTheUserFolder,
        '/permissionfoo/bars/userfolder/usertestobject' => $onTheUserFolder,
    ]);
});

it('lets a user list a folder above a document that user may see, although no rule names it', function () {

    $reachable = ['list' => [1, 1, 0], 'delete' => [1, 0, 0], 'publish' => [1, 0, 0], 'versions' => [1, 0, 0]];

    assertDocumentPermissions([
        '/permissioncpath/a' => $reachable,
        '/permissioncpath/a/b' => $reachable,
        '/permissioncpath/a/b/c' => $reachable,
    ]);
});
