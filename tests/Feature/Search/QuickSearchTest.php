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

use OpenDxp\Tests\Story\DataObjectPermissions as Tree;

beforeEach(fn () => Tree::load());

afterEach(fn () => Tree::forget());

/**
 * The quick search answers across every kind of element at once, so the table names every user. A
 * user who holds no module permission at all is handed nothing, however their workspaces read.
 */
it('hands every user only what that user may see', function (string $query, array $expected) {

    foreach (Tree::EVERY_USER as $name) {
        expect(quickSearchAs($name, $query))
            ->toEqualCanonicalizing($expected[$name] ?? [], sprintf('%s for %s', $query, $name));
    }
})->with([
    'an object only the administrator reaches' => ['hugo', [
        'admin' => ['/permissionfoo/bars/hugo'],
    ]],
    'the folder above three objects' => ['bars', [
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
    ]],
    'an object below a folder nobody is listed for' => ['hiddenobject', [
        'admin' => ['/permissionbar/foo/hiddenobject'],
    ]],
    'an asset beside the tree' => ['assetelement', [
        'admin' => ['/assetelement.gif'],
        'Permissiontest5' => ['/assetelement.gif'],
    ]],
]);
