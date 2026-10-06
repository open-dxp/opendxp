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

beforeEach(fn () => DocumentPermissions::load());

it('tells which elements hold children the user may see', function (
    string $userName,
    array $expected,
) {
    $user = User::getByName($userName);

    $actual = answersByPath(
        $expected,
        fn (string $path) => Document::getByPath($path)->getDao()->hasChildren(
            includingUnpublished: true,
            user: $user,
        ),
    );

    expect($actual)->toBe($expected);
})->with([
    'admin' => [
        'admin',
        [
            '/permissioncpath/a' => true,
            '/permissionfoo' => true,
            '/permissionfoo/bars' => true,
            '/permissionfoo/bars/hugo' => false,
            '/permissionfoo/bars/userfolder' => true,
            '/permissionfoo/bars/groupfolder' => true,
            '/permissionfoo/bars/groupfolder/grouptestobject' => false,
            '/permissionbar' => true,
            '/permissionbar/foo' => true,
            '/permissionbar/foo/hiddenobject' => false,
        ],
    ],
    'Permissiontest1' => [
        'Permissiontest1',
        [
            '/permissioncpath/a' => true,
            '/permissionfoo' => true,
            '/permissionfoo/bars' => true,
            '/permissionfoo/bars/hugo' => false,
            '/permissionfoo/bars/userfolder' => true,
            '/permissionfoo/bars/groupfolder' => true,
            '/permissionfoo/bars/groupfolder/grouptestobject' => false,
            '/permissionbar' => false,
            '/permissionbar/foo' => false,
            '/permissionbar/foo/hiddenobject' => false,
        ],
    ],
    'Permissiontest2' => [
        'Permissiontest2',
        [
            '/permissioncpath/a' => false,
            '/permissionfoo' => true,
            '/permissionfoo/bars' => true,
            '/permissionfoo/bars/hugo' => false,
            '/permissionfoo/bars/userfolder' => true,
            '/permissionfoo/bars/groupfolder' => false,
            '/permissionfoo/bars/groupfolder/grouptestobject' => false,
            '/permissionbar' => false,
            '/permissionbar/foo' => false,
            '/permissionbar/foo/hiddenobject' => false,
        ],
    ],
]);

it('tells which elements the user may list and view', function (
    string $userName,
    array $expected,
) {
    $user = User::getByName($userName);

    $actual = answersByPath(
        $expected,
        static function (string $path) use ($user): array {
            $element = Document::getByPath($path);

            return [
                'list' => $element->isAllowed('list', $user),
                'view' => $element->isAllowed('view', $user),
            ];
        },
    );

    expect($actual)->toBe($expected);
})->with([
    'admin' => [
        'admin',
        [
            '/permissionfoo' => [
                'list' => true,
                'view' => true,
            ],
            '/permissionfoo/bars' => [
                'list' => true,
                'view' => true,
            ],
            '/permissionfoo/bars/hugo' => [
                'list' => true,
                'view' => true,
            ],
            '/permissionfoo/bars/userfolder' => [
                'list' => true,
                'view' => true,
            ],
            '/permissionfoo/bars/groupfolder' => [
                'list' => true,
                'view' => true,
            ],
            '/permissionfoo/bars/groupfolder/grouptestobject' => [
                'list' => true,
                'view' => true,
            ],
            '/permissionbar' => [
                'list' => true,
                'view' => true,
            ],
            '/permissionbar/foo' => [
                'list' => true,
                'view' => true,
            ],
            '/permissionbar/foo/hiddenobject' => [
                'list' => true,
                'view' => true,
            ],
        ],
    ],
    'Permissiontest1' => [
        'Permissiontest1',
        [
            '/permissionfoo' => [
                'list' => true,
                'view' => true,
            ],
            '/permissionfoo/bars' => [
                'list' => true,
                'view' => false,
            ],
            '/permissionfoo/bars/hugo' => [
                'list' => false,
                'view' => false,
            ],
            '/permissionfoo/bars/userfolder' => [
                'list' => true,
                'view' => true,
            ],
            '/permissionfoo/bars/groupfolder' => [
                'list' => true,
                'view' => true,
            ],
            '/permissionfoo/bars/groupfolder/grouptestobject' => [
                'list' => true,
                'view' => true,
            ],
            '/permissionbar' => [
                'list' => true,
                'view' => true,
            ],
            '/permissionbar/foo' => [
                'list' => false,
                'view' => false,
            ],
            '/permissionbar/foo/hiddenobject' => [
                'list' => false,
                'view' => false,
            ],
        ],
    ],
    'Permissiontest2' => [
        'Permissiontest2',
        [
            '/permissionfoo' => [
                'list' => true,
                'view' => true,
            ],
            '/permissionfoo/bars' => [
                'list' => true,
                'view' => false,
            ],
            '/permissionfoo/bars/hugo' => [
                'list' => false,
                'view' => false,
            ],
            '/permissionfoo/bars/userfolder' => [
                'list' => true,
                'view' => true,
            ],
            '/permissionfoo/bars/groupfolder' => [
                'list' => false,
                'view' => false,
            ],
            '/permissionfoo/bars/groupfolder/grouptestobject' => [
                'list' => false,
                'view' => false,
            ],
            '/permissionbar' => [
                'list' => true,
                'view' => true,
            ],
            '/permissionbar/foo' => [
                'list' => false,
                'view' => false,
            ],
            '/permissionbar/foo/hiddenobject' => [
                'list' => false,
                'view' => false,
            ],
        ],
    ],
]);

it('applies a permission granted on a folder to the folder and its children', function (
    string $userName,
    array $expected,
) {
    $user = User::getByName($userName);

    $actual = answersByPath(
        $expected,
        static fn (string $path): array => permissionsNamed(
            Document::getByPath($path)->getUserPermissions($user),
            array_keys($expected[$path]),
        ),
    );

    expect($actual)->toBe($expected);
})->with([
    'admin' => [
        'admin',
        [
            '/permissionfoo/bars/groupfolder' => [
                'save' => 1,
                'delete' => 1,
                'publish' => 1,
                'unpublish' => 1,
                'versions' => 1,
            ],
            '/permissionfoo/bars/groupfolder/grouptestobject' => [
                'save' => 1,
                'delete' => 1,
                'publish' => 1,
                'unpublish' => 1,
                'versions' => 1,
            ],
            '/permissionfoo/bars/userfolder' => [
                'view' => 1,
                'delete' => 1,
                'publish' => 1,
                'versions' => 1,
                'create' => 1,
                'rename' => 1,
            ],
            '/permissionfoo/bars/userfolder/usertestobject' => [
                'view' => 1,
                'delete' => 1,
                'publish' => 1,
                'versions' => 1,
                'create' => 1,
                'rename' => 1,
            ],
        ],
    ],
    'Permissiontest1' => [
        'Permissiontest1',
        [
            '/permissionfoo/bars/groupfolder' => [
                'save' => 1,
                'delete' => 0,
                'publish' => 0,
                'unpublish' => 1,
                'versions' => 0,
            ],
            '/permissionfoo/bars/groupfolder/grouptestobject' => [
                'save' => 1,
                'delete' => 0,
                'publish' => 0,
                'unpublish' => 1,
                'versions' => 0,
            ],
            '/permissionfoo/bars/userfolder' => [
                'view' => 1,
                'delete' => 0,
                'publish' => 0,
                'versions' => 0,
                'create' => 1,
                'rename' => 1,
            ],
            '/permissionfoo/bars/userfolder/usertestobject' => [
                'view' => 1,
                'delete' => 0,
                'publish' => 0,
                'versions' => 0,
                'create' => 1,
                'rename' => 1,
            ],
        ],
    ],
    'Permissiontest2' => [
        'Permissiontest2',
        [
            '/permissionfoo/bars/groupfolder' => [
                'save' => 1,
                'delete' => 0,
                'publish' => 1,
                'unpublish' => 0,
                'versions' => 0,
            ],
            '/permissionfoo/bars/groupfolder/grouptestobject' => [
                'save' => 1,
                'delete' => 0,
                'publish' => 1,
                'unpublish' => 0,
                'versions' => 0,
            ],
            '/permissionfoo/bars/userfolder' => [
                'view' => 1,
                'delete' => 0,
                'publish' => 0,
                'versions' => 0,
                'create' => 0,
                'rename' => 0,
            ],
            '/permissionfoo/bars/userfolder/usertestobject' => [
                'view' => 1,
                'delete' => 0,
                'publish' => 0,
                'versions' => 0,
                'create' => 0,
                'rename' => 0,
            ],
        ],
    ],
]);

it('lets a user list the parent folders of an element the user may see', function (
    string $userName,
    array $expected,
) {
    $user = User::getByName($userName);

    $actual = answersByPath(
        $expected,
        static fn (string $path): array => permissionsNamed(
            Document::getByPath($path)->getUserPermissions($user),
            array_keys($expected[$path]),
        ),
    );

    expect($actual)->toBe($expected);
})->with([
    'admin' => [
        'admin',
        [
            '/permissioncpath/a' => [
                'list' => 1,
                'delete' => 1,
                'publish' => 1,
                'versions' => 1,
            ],
            '/permissioncpath/a/b' => [
                'list' => 1,
                'delete' => 1,
                'publish' => 1,
                'versions' => 1,
            ],
            '/permissioncpath/a/b/c' => [
                'list' => 1,
                'delete' => 1,
                'publish' => 1,
                'versions' => 1,
            ],
        ],
    ],
    'Permissiontest1' => [
        'Permissiontest1',
        [
            '/permissioncpath/a' => [
                'list' => 1,
                'delete' => 0,
                'publish' => 0,
                'versions' => 0,
            ],
            '/permissioncpath/a/b' => [
                'list' => 1,
                'delete' => 0,
                'publish' => 0,
                'versions' => 0,
            ],
            '/permissioncpath/a/b/c' => [
                'list' => 1,
                'delete' => 0,
                'publish' => 0,
                'versions' => 0,
            ],
        ],
    ],
    'Permissiontest2' => [
        'Permissiontest2',
        [
            '/permissioncpath/a' => [
                'list' => 0,
                'delete' => 0,
                'publish' => 0,
                'versions' => 0,
            ],
            '/permissioncpath/a/b' => [
                'list' => 0,
                'delete' => 0,
                'publish' => 0,
                'versions' => 0,
            ],
            '/permissioncpath/a/b/c' => [
                'list' => 0,
                'delete' => 0,
                'publish' => 0,
                'versions' => 0,
            ],
        ],
    ],
]);
