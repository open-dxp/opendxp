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

use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\AbstractElementFactory;
use OpenDxp\Test\Factory\AbstractUserRoleFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\Test\Factory\UserRoleFactory;
use Zenstruck\Foundry\Story;

/**
 * Two users see different parts of the same tree. Every kind of element builds this tree, so the permission tests
 * of all three read alike. Both users carry two roles, and both roles name the group folder.
 *
 * @method static ElementInterface root()
 * @method static ElementInterface permissionfoo()
 * @method static ElementInterface permissionbar()
 * @method static ElementInterface bars()
 * @method static ElementInterface userfolder()
 * @method static ElementInterface groupfolder()
 * @method static ElementInterface usertestobject()
 * @method static User\Role testrole()
 * @method static User\Role dummyRole()
 * @method static User permissiontest1()
 * @method static User permissiontest2()
 */
abstract class PermissionTree extends Story
{
    protected const string ROOT_KEY = 'permissioncpath';

    public function build(): void
    {
        $root = $this->topFolder(static::ROOT_KEY);
        $a = $this->folderIn($root, 'a');
        $b = $this->folderIn($a, 'b');
        $c = $this->elementIn($b, 'c');
        $abcdefghjkl = $this->elementIn($root, 'abcdefghjkl');

        $foo = $this->topFolder('permissionfoo');
        $bar = $this->topFolder('permissionbar');
        $hidden = $this->folderIn($bar, 'foo');
        $bars = $this->folderIn($foo, 'bars');
        $userfolder = $this->folderIn($bars, 'userfolder');
        $groupfolder = $this->folderIn($bars, 'groupfolder');

        $this->elementIn($hidden, 'hiddenobject');
        $this->elementIn($bars, 'hugo');
        $usertestobject = $this->elementIn($userfolder, 'usertestobject');
        $this->elementIn($groupfolder, 'grouptestobject');

        $testroleGrants = $this->testroleGrants();
        $testroleFactory = UserRoleFactory::new();
        $testroleFactory = $this->withWorkspace($testroleFactory, $groupfolder, ...$testroleGrants);
        $testrole = $testroleFactory->create(['name' => 'Testrole']);

        $dummyRoleGrants = $this->dummyRoleGrants();
        $dummyRoleFactory = UserRoleFactory::new();
        $dummyRoleFactory = $this->withWorkspace($dummyRoleFactory, $groupfolder, ...$dummyRoleGrants);
        $dummyRole = $dummyRoleFactory->create(['name' => 'dummyRole']);

        $module = $this->module();
        $first = UserFactory::new()
            ->withPermissions($module)
            ->withRoles($testrole, $dummyRole);
        $first = $this->withWorkspace($first, $foo, 'list', 'view');
        $first = $this->withWorkspace($first, $bar, 'list', 'view');
        $first = $this->withWorkspace($first, $hidden);
        $first = $this->withWorkspace($first, $bars);
        $first = $this->withWorkspace($first, $userfolder, 'list', 'view', 'create', 'rename');
        $first = $this->withWorkspace($first, $c, 'list', 'view');
        $first = $this->withWorkspace($first, $abcdefghjkl, 'list', 'view');

        $second = UserFactory::new()
            ->withPermissions($module)
            ->withRoles($testrole, $dummyRole);
        $second = $this->withWorkspace($second, $foo, 'list', 'view');
        $second = $this->withWorkspace($second, $bar, 'list', 'view');
        $second = $this->withWorkspace($second, $hidden);
        $second = $this->withWorkspace($second, $bars);
        $second = $this->withWorkspace($second, $userfolder, 'list', 'view');
        $secondUserGrants = $this->secondUserGrants();
        $second = $this->withWorkspace($second, $groupfolder, ...$secondUserGrants);

        $this->addState('root', $root);
        $this->addState('permissionfoo', $foo);
        $this->addState('permissionbar', $bar);
        $this->addState('bars', $bars);
        $this->addState('userfolder', $userfolder);
        $this->addState('groupfolder', $groupfolder);
        $this->addState('usertestobject', $usertestobject);
        $this->addState('testrole', $testrole);
        $this->addState('dummyRole', $dummyRole);
        $this->addState('permissiontest1', $first->create(['name' => 'Permissiontest1']));
        $this->addState('permissiontest2', $second->create(['name' => 'Permissiontest2']));
    }

    /**
     * A search test commits what the tree wrote, so it takes the tree back after the test.
     */
    public static function forget(): void
    {
        $written = [
            static::root(),
            static::permissionfoo(),
            static::permissionbar(),
            static::permissiontest1(),
            static::permissiontest2(),
            static::testrole(),
            static::dummyRole(),
        ];

        foreach ($written as $model) {
            $model->delete();
        }
    }

    abstract protected function folders(): AbstractElementFactory;

    /**
     * Returns a factory for an element of the tree that is not a folder.
     */
    abstract protected function element(string $key): AbstractElementFactory;

    /**
     * @template O of AbstractUserRoleFactory
     *
     * @param O $owner
     *
     * @return O
     */
    abstract protected function withWorkspace(
        AbstractUserRoleFactory $owner,
        ElementInterface $element,
        string ...$permissions,
    ): AbstractUserRoleFactory;

    /**
     * Names the permission that opens the backend module of this kind of element.
     */
    abstract protected function module(): string;

    /**
     * Testrole grants these permissions on the group folder.
     *
     * @return list<string>
     */
    abstract protected function testroleGrants(): array;

    /**
     * The dummy role grants these permissions on the group folder.
     *
     * @return list<string>
     */
    abstract protected function dummyRoleGrants(): array;

    /**
     * The second user is granted these permissions on the group folder directly.
     *
     * @return list<string>
     */
    abstract protected function secondUserGrants(): array;

    private function topFolder(string $key): ElementInterface
    {
        $folder = $this
            ->folders()
            ->create(['key' => $key]);

        $this->addToPool('elements', $folder);

        return $folder;
    }

    private function folderIn(ElementInterface $parent, string $key): ElementInterface
    {
        $folder = $this
            ->folders()
            ->withParent($parent)
            ->create(['key' => $key]);

        $this->addToPool('elements', $folder);

        return $folder;
    }

    private function elementIn(ElementInterface $parent, string $key): ElementInterface
    {
        $element = $this
            ->element($key)
            ->withParent($parent)
            ->create();

        $this->addToPool('elements', $element);

        return $element;
    }
}
