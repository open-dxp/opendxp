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

namespace OpenDxp\Tests\Feature\Search;

use OpenDxp\Tests\Story\DataObjectPermissions;

beforeEach(fn () => $this->loadTree(DataObjectPermissions::class));

// The quick search covers every kind of element. A user without any module permission finds nothing.
it('finds for each user only what that user may see', function (string $query, array $expected) {
    $users = array_keys($expected);

    $found = array_combine(
        $users,
        array_map(
            fn (string $user): array => $this->quickSearchAs($user, $query),
            $users,
        ),
    );

    expect($found)->toEqualCanonicalizing($expected);
})->with([
    'an object only the administrator reaches' => [
        'hugo',
        [
            'admin' => ['/permissionfoo/bars/hugo'],
            'Permissiontest1' => [],
            'Permissiontest2' => [],
            'Permissiontest3' => [],
            'Permissiontest4' => [],
            'Permissiontest5' => [],
            'Permissiontest6' => [],
        ],
    ],
    'the folder above three objects' => [
        'bars',
        [
            'admin' => [
                '/permissionfoo/bars/hugo',
                '/permissionfoo/bars/userfolder/usertestobject',
                '/permissionfoo/bars/groupfolder/grouptestobject',
            ],
            'Permissiontest1' => [
                '/permissionfoo/bars/userfolder/usertestobject',
                '/permissionfoo/bars/groupfolder/grouptestobject',
            ],
            'Permissiontest2' => ['/permissionfoo/bars/userfolder/usertestobject'],
            'Permissiontest3' => ['/permissionfoo/bars/userfolder/usertestobject'],
            'Permissiontest4' => ['/permissionfoo/bars/groupfolder/grouptestobject'],
            'Permissiontest5' => ['/permissionfoo/bars/userfolder/usertestobject'],
            'Permissiontest6' => [],
        ],
    ],
    'an object below a folder nobody is listed for' => [
        'hiddenobject',
        [
            'admin' => ['/permissionbar/foo/hiddenobject'],
            'Permissiontest1' => [],
            'Permissiontest2' => [],
            'Permissiontest3' => [],
            'Permissiontest4' => [],
            'Permissiontest5' => [],
            'Permissiontest6' => [],
        ],
    ],
    'an asset beside the tree' => [
        'assetelement',
        [
            'admin' => ['/assetelement.gif'],
            'Permissiontest1' => [],
            'Permissiontest2' => [],
            'Permissiontest3' => [],
            'Permissiontest4' => [],
            'Permissiontest5' => ['/assetelement.gif'],
            'Permissiontest6' => [],
        ],
    ],
]);
