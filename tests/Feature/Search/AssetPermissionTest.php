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
use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Tests\Story\AssetPermissions;

beforeEach(fn () => AssetPermissions::load());

afterEach(fn () => AssetPermissions::forget());

it('hands a user only the results that user may see', function () {

    $all = [
        '/permissionfoo/bars',
        '/permissionfoo/bars/hugo.gif',
        '/permissionfoo/bars/userfolder',
        '/permissionfoo/bars/userfolder/usertestobject.gif',
        '/permissionfoo/bars/groupfolder',
        '/permissionfoo/bars/groupfolder/grouptestobject.gif',
    ];

    expect(searchAs('admin', 'asset', 'bars'))
        ->toEqualCanonicalizing($all)
        ->and(searchAs('Permissiontest1', 'asset', 'bars'))
        ->toEqualCanonicalizing(array_values(array_diff($all, ['/permissionfoo/bars/hugo.gif'])))
        ->and(searchAs('Permissiontest2', 'asset', 'bars'))
        ->toEqualCanonicalizing([
            '/permissionfoo/bars',
            '/permissionfoo/bars/userfolder',
            '/permissionfoo/bars/userfolder/usertestobject.gif',
        ]);
});

it('hands a user nothing for an element that user may not see', function () {

    expect(searchAs('admin', 'asset', 'hugo'))
        ->toBe(['/permissionfoo/bars/hugo.gif'])
        ->and(searchAs('Permissiontest1', 'asset', 'hugo'))
        ->toBe([])
        ->and(searchAs('Permissiontest2', 'asset', 'hugo'))
        ->toBe([])
        ->and(searchAs('admin', 'asset', 'hiddenobject'))
        ->toBe(['/permissionbar/foo/hiddenobject.gif'])
        ->and(searchAs('Permissiontest1', 'asset', 'hiddenobject'))
        ->toBe([])
        ->and(searchAs('Permissiontest2', 'asset', 'hiddenobject'))
        ->toBe([]);
});

it('fills a limited page with the results a user may see, not with the ones it filtered out', function () {

    $folder = indexed(AssetFolderFactory::createOne(['key' => 'manyElements', 'parentId' => 1]));

    foreach (range(1, 5) as $number) {
        indexed(AssetImageFactory::createOne(['key' => sprintf('manyelement %d.gif', $number), 'parentId' => $folder->getId()]));
    }

    $visible = indexed(AssetImageFactory::createOne(['key' => 'manyelement X.gif', 'parentId' => $folder->getId()]));

    $role = User\Role::getByName('Testrole');
    $role->setWorkspacesAsset([
        assetWorkspace($visible->getRealFullPath(), AssetPermissions::SEES),
        assetWorkspace('/permissionfoo/bars/groupfolder', AssetPermissions::SEES),
    ]);
    $role->save();

    expect(searchAs('admin', 'asset', 'manyelement', 6))->toHaveCount(6);

    foreach (['Permissiontest1', 'Permissiontest2'] as $name) {
        expect(searchAs($name, 'asset', 'manyelement', 6))
            ->toBe([$visible->getRealFullPath()])
            ->and(searchAs($name, 'asset', 'manyelement', 5))
            ->toBe([$visible->getRealFullPath()]);
    }
});
