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
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\AbstractElementFactory;
use OpenDxp\Test\Factory\AbstractUserRoleFactory;
use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\AssetImageFactory;

/**
 * @method static Asset\Folder root()
 * @method static Asset\Folder permissionfoo()
 * @method static Asset\Folder permissionbar()
 * @method static Asset\Folder bars()
 * @method static Asset\Folder userfolder()
 * @method static Asset\Folder groupfolder()
 * @method static Asset\Image usertestobject()
 * @method static User\Role testrole()
 * @method static User\Role dummyRole()
 * @method static User permissiontest1()
 * @method static User permissiontest2()
 */
final class AssetPermissions extends PermissionTree
{
    protected function folders(): AbstractElementFactory
    {
        return AssetFolderFactory::new();
    }

    protected function element(string $key): AbstractElementFactory
    {
        return AssetImageFactory::new()
            ->with(['key' => $key . '.gif']);
    }

    protected function withWorkspace(
        AbstractUserRoleFactory $owner,
        ElementInterface $element,
        string ...$permissions,
    ): AbstractUserRoleFactory {
        return $owner->withAssetWorkspace($element, ...$permissions);
    }

    protected function module(): string
    {
        return 'assets';
    }

    protected function testroleGrants(): array
    {
        return [
            'list',
            'view',
        ];
    }

    protected function dummyRoleGrants(): array
    {
        return [];
    }

    protected function secondUserGrants(): array
    {
        return [
            'delete',
            'publish',
        ];
    }
}
