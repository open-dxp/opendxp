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

use OpenDxp\Model\User;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Tests\Factory\UnittestFactory;
use OpenDxp\Tests\Story\DataObjectPermissions as Tree;

beforeEach(fn () => Tree::load());

afterEach(fn () => Tree::forget());

it('hands a user only the results that user may see', function () {

    $all = [
        '/permissionfoo/bars',
        '/permissionfoo/bars/hugo',
        '/permissionfoo/bars/userfolder',
        '/permissionfoo/bars/userfolder/usertestobject',
        '/permissionfoo/bars/groupfolder',
        '/permissionfoo/bars/groupfolder/grouptestobject',
    ];

    expect(searchAs('admin', 'object', 'bars'))
        ->toEqualCanonicalizing($all)
        ->and(searchAs('Permissiontest1', 'object', 'bars'))
        ->toEqualCanonicalizing(array_values(array_diff($all, ['/permissionfoo/bars/hugo'])))
        ->and(searchAs('Permissiontest2', 'object', 'bars'))
        ->toEqualCanonicalizing([
            '/permissionfoo/bars',
            '/permissionfoo/bars/userfolder',
            '/permissionfoo/bars/userfolder/usertestobject',
        ]);
});

it('hands a user nothing for an object that user may not see', function () {
    expect(searchAs('admin', 'object', 'hugo'))
        ->toBe(['/permissionfoo/bars/hugo'])
        ->and(searchAs('Permissiontest1', 'object', 'hugo'))
        ->toBe([])
        ->and(searchAs('Permissiontest2', 'object', 'hugo'))
        ->toBe([])
        ->and(searchAs('admin', 'object', 'hiddenobject'))
        ->toBe(['/permissionbar/foo/hiddenobject'])
        ->and(searchAs('Permissiontest1', 'object', 'hiddenobject'))
        ->toBe([])
        ->and(searchAs('Permissiontest2', 'object', 'hiddenobject'))
        ->toBe([]);
});

it('fills a limited page with the objects a user may see, not with the ones it filtered out', function () {

    $folder = indexed(DataObjectFolderFactory::createOne(['key' => 'manyElements', 'parentId' => 1]));

    foreach (range(1, 5) as $number) {
        indexed(UnittestFactory::createOne(['key' => sprintf('manyelement %d', $number), 'input' => sprintf('manyelement %d', $number), 'parentId' => $folder->getId()]));
    }

    $visible = indexed(UnittestFactory::createOne(['key' => 'manyelement X', 'input' => 'manyelement X', 'parentId' => $folder->getId()]));

    $role = User\Role::getByName('Testrole');
    $role->setWorkspacesObject([
        objectWorkspace($visible->getRealFullPath(), Tree::SEES),
        objectWorkspace('/permissionfoo/bars/groupfolder', Tree::SEES),
    ]);
    $role->save();

    expect(searchAs('admin', 'object', 'manyelement', 6))->toHaveCount(6);

    foreach (['Permissiontest1', 'Permissiontest2'] as $name) {
        expect(searchAs($name, 'object', 'manyelement', 6))
            ->toBe([$visible->getRealFullPath()])
            ->and(searchAs($name, 'object', 'manyelement', 5))
            ->toBe([$visible->getRealFullPath()]);
    }
});
