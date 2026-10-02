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

use OpenDxp\Model\Document;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\DocumentFolderFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\Test\Factory\UserRoleFactory;
use Zenstruck\Foundry\Story;

final class DocumentPermissions extends Story
{
    public const array USERS = ['admin', 'Permissiontest1', 'Permissiontest2'];

    public const array SEES = ['list' => true, 'view' => true];

    public const array BLIND = ['list' => false, 'view' => false];

    public function build(): void
    {
        $root = indexed(DocumentFolderFactory::createOne(['key' => 'permissioncpath', 'parentId' => 1]));
        $a = indexed(DocumentFolderFactory::createOne(['key' => 'a', 'parentId' => $root->getId()]));
        $b = indexed(DocumentFolderFactory::createOne(['key' => 'b', 'parentId' => $a->getId()]));
        indexed(DocumentPageFactory::createOne(['key' => 'c', 'parentId' => $b->getId()]));
        indexed(DocumentPageFactory::createOne(['key' => 'abcdefghjkl', 'parentId' => $root->getId()]));

        $foo = indexed(DocumentFolderFactory::createOne(['key' => 'permissionfoo', 'parentId' => 1]));
        $bar = indexed(DocumentFolderFactory::createOne(['key' => 'permissionbar', 'parentId' => 1]));
        $hidden = indexed(DocumentFolderFactory::createOne(['key' => 'foo', 'parentId' => $bar->getId()]));
        $bars = indexed(DocumentFolderFactory::createOne(['key' => 'bars', 'parentId' => $foo->getId()]));
        $userfolder = indexed(DocumentFolderFactory::createOne(['key' => 'userfolder', 'parentId' => $bars->getId()]));
        $groupfolder = indexed(DocumentFolderFactory::createOne(['key' => 'groupfolder', 'parentId' => $bars->getId()]));

        indexed(DocumentPageFactory::createOne(['key' => 'hiddenobject', 'parentId' => $hidden->getId()]));
        indexed(DocumentPageFactory::createOne(['key' => 'hugo', 'parentId' => $bars->getId()]));
        indexed(DocumentPageFactory::createOne(['key' => 'usertestobject', 'parentId' => $userfolder->getId()]));
        indexed(DocumentPageFactory::createOne(['key' => 'grouptestobject', 'parentId' => $groupfolder->getId()]));

        UserRoleFactory::createOne([
            'name' => 'Testrole',
            'workspacesDocument' => [
                documentWorkspace('/permissionfoo/bars/groupfolder', [...self::SEES, 'save' => true, 'publish' => false]),
            ],
        ]);
        UserRoleFactory::createOne([
            'name' => 'dummyRole',
            'workspacesDocument' => [
                documentWorkspace('/permissionfoo/bars/groupfolder', [
                    ...self::BLIND, 'save' => false, 'publish' => false, 'unpublish' => true,
                ]),
            ],
        ]);

        $roles = [User\Role::getByName('Testrole')->getId(), User\Role::getByName('dummyRole')->getId()];

        UserFactory::createOne([
            'name' => 'Permissiontest1',
            'permissions' => ['documents'],
            'roles' => $roles,
            'workspacesDocument' => [
                documentWorkspace('/permissionfoo', self::SEES),
                documentWorkspace('/permissionbar', self::SEES),
                documentWorkspace('/permissionbar/foo', self::BLIND),
                documentWorkspace('/permissionfoo/bars', self::BLIND),
                documentWorkspace('/permissionfoo/bars/userfolder', [...self::SEES, 'create' => true, 'rename' => true]),
                documentWorkspace('/permissioncpath/a/b/c', self::SEES),
                documentWorkspace('/permissioncpath/abcdefghjkl', self::SEES),
            ],
        ]);
        UserFactory::createOne([
            'name' => 'Permissiontest2',
            'permissions' => ['documents'],
            'roles' => $roles,
            'workspacesDocument' => [
                documentWorkspace('/permissionfoo', self::SEES),
                documentWorkspace('/permissionbar', self::SEES),
                documentWorkspace('/permissionbar/foo', self::BLIND),
                documentWorkspace('/permissionfoo/bars', self::BLIND),
                documentWorkspace('/permissionfoo/bars/userfolder', self::SEES),
                documentWorkspace('/permissionfoo/bars/groupfolder', [
                    ...self::BLIND, 'save' => true, 'publish' => true, 'unpublish' => false,
                ]),
            ],
        ]);
    }

    /**
     * A test that cannot roll back takes back what the story wrote.
     */
    public static function forget(): void
    {
        foreach (['/permissioncpath', '/permissionfoo', '/permissionbar', '/manyElements'] as $path) {
            Document::getByPath($path)?->delete();
        }

        foreach (['Permissiontest1', 'Permissiontest2'] as $name) {
            User::getByName($name)?->delete();
        }

        foreach (['Testrole', 'dummyRole'] as $name) {
            User\Role::getByName($name)?->delete();
        }
    }
}
