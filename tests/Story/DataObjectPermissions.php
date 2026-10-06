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
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\AbstractElementFactory;
use OpenDxp\Test\Factory\AbstractUserRoleFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

/**
 * The quick search answers for every user at once, so the object tree has four more users and an asset beside it.
 *
 * @method static DataObject\Folder root()
 * @method static DataObject\Folder permissionfoo()
 * @method static DataObject\Folder permissionbar()
 * @method static DataObject\Folder bars()
 * @method static DataObject\Folder userfolder()
 * @method static DataObject\Folder groupfolder()
 * @method static DataObject\Unittest usertestobject()
 * @method static User\Role testrole()
 * @method static User\Role dummyRole()
 * @method static User permissiontest1()
 * @method static User permissiontest2()
 * @method static User permissiontest3()
 * @method static User permissiontest4()
 * @method static User permissiontest5()
 * @method static User permissiontest6()
 * @method static Asset\Image assetelement()
 */
final class DataObjectPermissions extends PermissionTree
{
    /**
     * The quotes belong to the name. A path reaches the permission query as a string, so one that carries quotes
     * has to survive it.
     */
    protected const string ROOT_KEY = 'permission\'"cpath';

    public function build(): void
    {
        parent::build();

        $usertestobject = self::usertestobject();
        $assetelement = AssetImageFactory::createOne(['key' => 'assetelement.gif']);
        $this->addToPool('elements', $assetelement);

        $third = UserFactory::new()
            ->withPermissions('objects')
            ->withObjectWorkspace($usertestobject, 'list', 'view');
        $fourth = UserFactory::new()
            ->withPermissions('objects')
            ->withRoles(
                self::testrole(),
                self::dummyRole(),
            );
        $fifth = UserFactory::new()
            ->withPermissions('assets', 'objects')
            ->withObjectWorkspace($usertestobject, 'list', 'view')
            ->withAssetWorkspace($assetelement, 'list', 'view');
        $sixth = UserFactory::new()
            ->withObjectWorkspace($usertestobject, 'list', 'view')
            ->withAssetWorkspace($assetelement, 'list', 'view');

        $this->addState('assetelement', $assetelement);
        $this->addState('permissiontest3', $third->create(['name' => 'Permissiontest3']));
        $this->addState('permissiontest4', $fourth->create(['name' => 'Permissiontest4']));
        $this->addState('permissiontest5', $fifth->create(['name' => 'Permissiontest5']));
        $this->addState('permissiontest6', $sixth->create(['name' => 'Permissiontest6']));
    }

    public static function forget(): void
    {
        parent::forget();

        $written = [
            self::assetelement(),
            self::permissiontest3(),
            self::permissiontest4(),
            self::permissiontest5(),
            self::permissiontest6(),
        ];

        foreach ($written as $model) {
            $model->delete();
        }
    }

    protected function folders(): AbstractElementFactory
    {
        return DataObjectFolderFactory::new();
    }

    protected function element(string $key): AbstractElementFactory
    {
        return UnittestFactory::new()
            ->with([
                'key' => $key,
                'input' => $key,
            ]);
    }

    protected function withWorkspace(
        AbstractUserRoleFactory $owner,
        ElementInterface $element,
        string ...$permissions,
    ): AbstractUserRoleFactory {
        return $owner->withObjectWorkspace($element, ...$permissions);
    }

    protected function module(): string
    {
        return 'objects';
    }

    protected function testroleGrants(): array
    {
        return [
            'list',
            'view',
            'save',
        ];
    }

    protected function dummyRoleGrants(): array
    {
        return ['settings'];
    }

    protected function secondUserGrants(): array
    {
        return [
            'save',
            'publish',
        ];
    }
}
