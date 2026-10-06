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

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\AbstractObject;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\User\UserRole;
use OpenDxp\Model\User\Workspace;

/**
 * A workspace grants the permissions a state names, such as list or view, and denies all others.
 *
 * @template T of UserRole
 *
 * @extends AbstractSavingFactory<T>
 */
abstract class AbstractUserRoleFactory extends AbstractSavingFactory
{
    public function withPermissions(string ...$permissions): static
    {
        return $this->with(['permissions' => $permissions]);
    }

    public function withAssetWorkspace(Asset $asset, string ...$permissions): static
    {
        return $this->afterInstantiate(
            static function (UserRole $owner) use ($asset, $permissions): void {
                $owner->setWorkspacesAsset([
                    ...$owner->getWorkspacesAsset(),
                    self::workspace(
                        new Workspace\Asset(),
                        $asset,
                        $permissions,
                    ),
                ]);
            },
        );
    }

    public function withDocumentWorkspace(Document $document, string ...$permissions): static
    {
        return $this->afterInstantiate(
            static function (UserRole $owner) use ($document, $permissions): void {
                $owner->setWorkspacesDocument([
                    ...$owner->getWorkspacesDocument(),
                    self::workspace(
                        new Workspace\Document(),
                        $document,
                        $permissions,
                    ),
                ]);
            },
        );
    }

    public function withObjectWorkspace(AbstractObject $object, string ...$permissions): static
    {
        return $this->afterInstantiate(
            static function (UserRole $owner) use ($object, $permissions): void {
                $owner->setWorkspacesObject([
                    ...$owner->getWorkspacesObject(),
                    self::workspace(
                        new Workspace\DataObject(),
                        $object,
                        $permissions,
                    ),
                ]);
            },
        );
    }

    protected function defaults(): array
    {
        return ['parentId' => 0];
    }

    /**
     * @template W of Workspace\AbstractWorkspace
     *
     * @param W $workspace
     * @param list<string> $permissions
     *
     * @return W
     */
    private static function workspace(
        Workspace\AbstractWorkspace $workspace,
        ElementInterface $element,
        array $permissions,
    ): Workspace\AbstractWorkspace {
        $workspace->setValues([
            'cId'   => $element->getId(),
            'cPath' => $element->getRealFullPath(),
            ...array_fill_keys($permissions, true),
        ]);

        return $workspace;
    }
}
