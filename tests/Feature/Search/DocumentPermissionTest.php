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
use OpenDxp\Test\Factory\DocumentFolderFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Story\DocumentPermissions;

beforeEach(fn () => DocumentPermissions::load());

afterEach(fn () => DocumentPermissions::forget());

it('hands a user only the results that user may see', function () {

    $all = [
        '/permissionfoo/bars',
        '/permissionfoo/bars/hugo',
        '/permissionfoo/bars/userfolder',
        '/permissionfoo/bars/userfolder/usertestobject',
        '/permissionfoo/bars/groupfolder',
        '/permissionfoo/bars/groupfolder/grouptestobject',
    ];

    expect(searchAs('admin', 'document', 'bars'))
        ->toEqualCanonicalizing($all)
        ->and(searchAs('Permissiontest1', 'document', 'bars'))
        ->toEqualCanonicalizing(array_values(array_diff($all, ['/permissionfoo/bars/hugo'])))
        ->and(searchAs('Permissiontest2', 'document', 'bars'))
        ->toEqualCanonicalizing([
            '/permissionfoo/bars',
            '/permissionfoo/bars/userfolder',
            '/permissionfoo/bars/userfolder/usertestobject',
        ]);
});

it('hands a user nothing for a document that user may not see', function () {
    expect(searchAs('admin', 'document', 'hugo'))
        ->toBe(['/permissionfoo/bars/hugo'])
        ->and(searchAs('Permissiontest1', 'document', 'hugo'))
        ->toBe([])
        ->and(searchAs('Permissiontest2', 'document', 'hugo'))
        ->toBe([])
        ->and(searchAs('admin', 'document', 'hiddenobject'))
        ->toBe(['/permissionbar/foo/hiddenobject'])
        ->and(searchAs('Permissiontest1', 'document', 'hiddenobject'))
        ->toBe([])
        ->and(searchAs('Permissiontest2', 'document', 'hiddenobject'))
        ->toBe([]);
});

it('fills a limited page with the documents a user may see, not with the ones it filtered out', function () {

    $folder = indexed(DocumentFolderFactory::createOne(['key' => 'manyElements', 'parentId' => 1]));

    foreach (range(1, 5) as $number) {
        indexed(DocumentPageFactory::createOne(['key' => sprintf('manyelement %d', $number), 'parentId' => $folder->getId()]));
    }

    $visible = indexed(DocumentPageFactory::createOne(['key' => 'manyelement X', 'parentId' => $folder->getId()]));

    $role = User\Role::getByName('Testrole');
    $role->setWorkspacesDocument([
        documentWorkspace($visible->getRealFullPath(), DocumentPermissions::SEES),
        documentWorkspace('/permissionfoo/bars/groupfolder', DocumentPermissions::SEES),
    ]);
    $role->save();

    expect(searchAs('admin', 'document', 'manyelement', 6))->toHaveCount(6);

    foreach (['Permissiontest1', 'Permissiontest2'] as $name) {
        expect(searchAs($name, 'document', 'manyelement', 6))
            ->toBe([$visible->getRealFullPath()])
            ->and(searchAs($name, 'document', 'manyelement', 5))
            ->toBe([$visible->getRealFullPath()]);
    }
});
