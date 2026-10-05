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


namespace OpenDxp\Tests\Story;

use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\Test\Factory\UserRoleFactory;
use OpenDxp\Tests\Factory\UnittestFactory;
use Zenstruck\Foundry\Story;

final class DataObjectPermissions extends Story
{
    public const array USERS = ['admin', 'Permissiontest1', 'Permissiontest2'];

    /**
     * The quick search answers for every user, so it needs the four that only exist for it.
     */
    public const array EVERY_USER = [
        'admin', 'Permissiontest1', 'Permissiontest2',
        'Permissiontest3', 'Permissiontest4', 'Permissiontest5', 'Permissiontest6',
    ];

    public const array SEES = ['list' => true, 'view' => true];

    public const array BLIND = ['list' => false, 'view' => false];

    /**
     * The quotes belong to the name. A path reaches the permission query as a string, so one that
     * carries quotes has to survive it.
     */
    public const string ROOT = '/permission\'"cpath';

    public function build(): void
    {
        $root = indexed(DataObjectFolderFactory::createOne(['key' => 'permission\'"cpath', 'parentId' => 1]));
        $a = indexed(DataObjectFolderFactory::createOne(['key' => 'a', 'parentId' => $root->getId()]));
        $b = indexed(DataObjectFolderFactory::createOne(['key' => 'b', 'parentId' => $a->getId()]));
        indexed(UnittestFactory::createOne(['key' => 'c', 'input' => 'c', 'parentId' => $b->getId()]));
        indexed(UnittestFactory::createOne(['key' => 'abcdefghjkl', 'input' => 'abcdefghjkl', 'parentId' => $root->getId()]));

        $foo = indexed(DataObjectFolderFactory::createOne(['key' => 'permissionfoo', 'parentId' => 1]));
        $bar = indexed(DataObjectFolderFactory::createOne(['key' => 'permissionbar', 'parentId' => 1]));
        $hidden = indexed(DataObjectFolderFactory::createOne(['key' => 'foo', 'parentId' => $bar->getId()]));
        $bars = indexed(DataObjectFolderFactory::createOne(['key' => 'bars', 'parentId' => $foo->getId()]));
        $userfolder = indexed(DataObjectFolderFactory::createOne(['key' => 'userfolder', 'parentId' => $bars->getId()]));
        $groupfolder = indexed(DataObjectFolderFactory::createOne(['key' => 'groupfolder', 'parentId' => $bars->getId()]));

        indexed(UnittestFactory::createOne(['key' => 'hiddenobject', 'input' => 'hiddenobject', 'parentId' => $hidden->getId()]));
        indexed(UnittestFactory::createOne(['key' => 'hugo', 'input' => 'hugo', 'parentId' => $bars->getId()]));
        indexed(UnittestFactory::createOne(['key' => 'usertestobject', 'input' => 'usertestobject', 'parentId' => $userfolder->getId()]));
        indexed(UnittestFactory::createOne(['key' => 'grouptestobject', 'input' => 'grouptestobject', 'parentId' => $groupfolder->getId()]));

        indexed(AssetImageFactory::createOne(['key' => 'assetelement.gif', 'parentId' => 1]));

        UserRoleFactory::createOne([
            'name' => 'Testrole',
            'workspacesObject' => [
                objectWorkspace('/permissionfoo/bars/groupfolder', [...self::SEES, 'save' => true, 'publish' => false]),
            ],
        ]);
        UserRoleFactory::createOne([
            'name' => 'dummyRole',
            'workspacesObject' => [
                objectWorkspace('/permissionfoo/bars/groupfolder', [
                    ...self::BLIND, 'save' => false, 'publish' => false, 'settings' => true,
                ]),
            ],
        ]);

        $roles = [User\Role::getByName('Testrole')->getId(), User\Role::getByName('dummyRole')->getId()];

        UserFactory::createOne([
            'name' => 'Permissiontest1',
            'permissions' => ['objects'],
            'roles' => $roles,
            'workspacesObject' => [
                objectWorkspace('/permissionfoo', self::SEES),
                objectWorkspace('/permissionbar', self::SEES),
                objectWorkspace('/permissionbar/foo', self::BLIND),
                objectWorkspace('/permissionfoo/bars', self::BLIND),
                objectWorkspace('/permissionfoo/bars/userfolder', [...self::SEES, 'create' => true, 'rename' => true]),
                objectWorkspace(self::ROOT . '/a/b/c', self::SEES),
                objectWorkspace(self::ROOT . '/abcdefghjkl', self::SEES),
            ],
        ]);
        UserFactory::createOne([
            'name' => 'Permissiontest2',
            'permissions' => ['objects'],
            'roles' => $roles,
            'workspacesObject' => [
                objectWorkspace('/permissionfoo', self::SEES),
                objectWorkspace('/permissionbar', self::SEES),
                objectWorkspace('/permissionbar/foo', self::BLIND),
                objectWorkspace('/permissionfoo/bars', self::BLIND),
                objectWorkspace('/permissionfoo/bars/userfolder', self::SEES),
                objectWorkspace('/permissionfoo/bars/groupfolder', [
                    ...self::BLIND, 'save' => true, 'publish' => true, 'settings' => false,
                ]),
            ],
        ]);

        $onOneObject = [objectWorkspace('/permissionfoo/bars/userfolder/usertestobject', self::SEES)];
        $onTheAsset = [assetWorkspace('/assetelement.gif', self::SEES)];

        UserFactory::createOne(['name' => 'Permissiontest3', 'permissions' => ['objects'], 'workspacesObject' => $onOneObject]);
        UserFactory::createOne(['name' => 'Permissiontest4', 'permissions' => ['objects'], 'roles' => $roles]);
        UserFactory::createOne([
            'name' => 'Permissiontest5',
            'permissions' => ['assets', 'objects'],
            'workspacesObject' => $onOneObject,
            'workspacesAsset' => $onTheAsset,
        ]);
        UserFactory::createOne([
            'name' => 'Permissiontest6',
            'permissions' => [],
            'workspacesObject' => $onOneObject,
            'workspacesAsset' => $onTheAsset,
        ]);
    }

    /**
     * A test that cannot roll back takes back what the story wrote.
     */
    public static function forget(): void
    {
        foreach ([self::ROOT, '/permissionfoo', '/permissionbar', '/manyElements'] as $path) {
            DataObject::getByPath($path)?->delete();
        }

        Asset::getByPath('/assetelement.gif')?->delete();

        foreach (self::EVERY_USER as $name) {
            if ($name !== 'admin') {
                User::getByName($name)?->delete();
            }
        }

        foreach (['Testrole', 'dummyRole'] as $name) {
            User\Role::getByName($name)?->delete();
        }
    }
}
