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

namespace OpenDxp\Model\Document\Editable\Block;

use OpenDxp\Model\Document;

class Item extends AbstractBlockItem
{
    protected function getItemType(): string
    {
        return 'block';
    }

    #[\Override]
    public function __call(string $func, array $args): ?Document\Editable
    {
        $element = $this->getEditable($args[0]);
        $class = 'OpenDxp\\Model\\Document\\Editable\\' . str_replace('get', '', $func);

        if (!$element instanceof \OpenDxp\Model\Document\Editable) {
            return new $class;
        }

        if (!strcasecmp($element::class, $class)) {
            return $element;
        }

        return null;
    }
}
