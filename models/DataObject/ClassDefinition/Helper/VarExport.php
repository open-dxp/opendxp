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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Model\DataObject\ClassDefinition\Helper;

/**
 * @internal
 */
trait VarExport
{
    protected array $blockedVarsForExport = [];

    public function getBlockedVarsForExport(): array
    {
        return $this->blockedVarsForExport;
    }

    public function resolveBlockedVars(): array
    {
        $defaultBlockedVars = [
            'fieldDefinitionsCache',
            'columnType',
            'queryColumnType',
        ];

        return [...$defaultBlockedVars, ...$this->getBlockedVarsForExport()];
    }

    public static function __set_state(array $data): static
    {
        $obj = new static();
        $obj->setValues($data);

        return $obj;
    }

    public function setBlockedVarsForExport(array $vars): static
    {
        $this->blockedVarsForExport = $vars;

        return $this;
    }
}
