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
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\AbstractElementFactory;
use OpenDxp\Test\Factory\AbstractUserRoleFactory;
use OpenDxp\Test\Factory\DocumentFolderFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;

/**
 * @method static Document\Folder root()
 * @method static Document\Folder permissionfoo()
 * @method static Document\Folder permissionbar()
 * @method static Document\Folder bars()
 * @method static Document\Folder userfolder()
 * @method static Document\Folder groupfolder()
 * @method static Document\Page usertestobject()
 * @method static User\Role testrole()
 * @method static User\Role dummyRole()
 * @method static User permissiontest1()
 * @method static User permissiontest2()
 */
final class DocumentPermissions extends PermissionTree
{
    protected function folders(): AbstractElementFactory
    {
        return DocumentFolderFactory::new();
    }

    protected function element(string $key): AbstractElementFactory
    {
        return DocumentPageFactory::new()
            ->with(['key' => $key]);
    }

    protected function withWorkspace(
        AbstractUserRoleFactory $owner,
        ElementInterface $element,
        string ...$permissions,
    ): AbstractUserRoleFactory {
        return $owner->withDocumentWorkspace($element, ...$permissions);
    }

    protected function module(): string
    {
        return 'documents';
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
        return ['unpublish'];
    }

    protected function secondUserGrants(): array
    {
        return [
            'save',
            'publish',
        ];
    }
}
