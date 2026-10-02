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
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\Test\Factory\UserRoleFactory;
use Zenstruck\Foundry\Story;

final class AssetPermissions extends Story
{
    public const array USERS = ['admin', 'Permissiontest1', 'Permissiontest2'];

    public const array SEES = ['list' => true, 'view' => true];

    public const array BLIND = ['list' => false, 'view' => false];

    public function build(): void
    {
        $root = indexed(AssetFolderFactory::createOne(['key' => 'permissioncpath', 'parentId' => 1]));
        $a = indexed(AssetFolderFactory::createOne(['key' => 'a', 'parentId' => $root->getId()]));
        $b = indexed(AssetFolderFactory::createOne(['key' => 'b', 'parentId' => $a->getId()]));
        indexed(AssetImageFactory::createOne(['key' => 'c.gif', 'parentId' => $b->getId()]));
        indexed(AssetImageFactory::createOne(['key' => 'abcdefghjkl.gif', 'parentId' => $root->getId()]));

        $foo = indexed(AssetFolderFactory::createOne(['key' => 'permissionfoo', 'parentId' => 1]));
        $bar = indexed(AssetFolderFactory::createOne(['key' => 'permissionbar', 'parentId' => 1]));
        $hidden = indexed(AssetFolderFactory::createOne(['key' => 'foo', 'parentId' => $bar->getId()]));
        $bars = indexed(AssetFolderFactory::createOne(['key' => 'bars', 'parentId' => $foo->getId()]));
        $bars->setProperty('foobar', 'input', 'bars', inherited: false, inheritable: true);
        $bars->save();
        $userfolder = indexed(AssetFolderFactory::createOne(['key' => 'userfolder', 'parentId' => $bars->getId()]));
        $groupfolder = indexed(AssetFolderFactory::createOne(['key' => 'groupfolder', 'parentId' => $bars->getId()]));

        indexed(AssetImageFactory::createOne(['key' => 'hiddenobject.gif', 'parentId' => $hidden->getId()]));
        indexed(AssetImageFactory::createOne(['key' => 'hugo.gif', 'parentId' => $bars->getId()]));
        indexed(AssetImageFactory::createOne(['key' => 'usertestobject.gif', 'parentId' => $userfolder->getId()]));
        indexed(AssetImageFactory::createOne(['key' => 'grouptestobject.gif', 'parentId' => $groupfolder->getId()]));

        UserRoleFactory::createOne([
            'name' => 'Testrole',
            'workspacesAsset' => [assetWorkspace('/permissionfoo/bars/groupfolder', self::SEES)],
        ]);
        UserRoleFactory::createOne([
            'name' => 'dummyRole',
            'workspacesAsset' => [
                assetWorkspace('/permissionfoo/bars/groupfolder', [...self::BLIND, 'delete' => false, 'publish' => false]),
            ],
        ]);

        $roles = [User\Role::getByName('Testrole')->getId(), User\Role::getByName('dummyRole')->getId()];

        UserFactory::createOne([
            'name' => 'Permissiontest1',
            'permissions' => ['assets'],
            'roles' => $roles,
            'workspacesAsset' => [
                assetWorkspace('/permissionfoo', self::SEES),
                assetWorkspace('/permissionbar', self::SEES),
                assetWorkspace('/permissionbar/foo', self::BLIND),
                assetWorkspace('/permissionfoo/bars', self::BLIND),
                assetWorkspace('/permissionfoo/bars/userfolder', [...self::SEES, 'create' => true, 'rename' => true]),
                assetWorkspace('/permissioncpath/a/b/c.gif', self::SEES),
                assetWorkspace('/permissioncpath/abcdefghjkl.gif', self::SEES),
            ],
        ]);
        UserFactory::createOne([
            'name' => 'Permissiontest2',
            'permissions' => ['assets'],
            'roles' => $roles,
            'workspacesAsset' => [
                assetWorkspace('/permissionfoo', self::SEES),
                assetWorkspace('/permissionbar', self::SEES),
                assetWorkspace('/permissionbar/foo', self::BLIND),
                assetWorkspace('/permissionfoo/bars', self::BLIND),
                assetWorkspace('/permissionfoo/bars/userfolder', self::SEES),
                assetWorkspace('/permissionfoo/bars/groupfolder', [...self::BLIND, 'delete' => true, 'publish' => true]),
            ],
        ]);
    }

    /**
     * A test that cannot roll back takes back what the story wrote.
     */
    public static function forget(): void
    {
        foreach (['/permissioncpath', '/permissionfoo', '/permissionbar', '/manyElements'] as $path) {
            Asset::getByPath($path)?->delete();
        }

        foreach (['Permissiontest1', 'Permissiontest2'] as $name) {
            User::getByName($name)?->delete();
        }

        foreach (['Testrole', 'dummyRole'] as $name) {
            User\Role::getByName($name)?->delete();
        }
    }
}
