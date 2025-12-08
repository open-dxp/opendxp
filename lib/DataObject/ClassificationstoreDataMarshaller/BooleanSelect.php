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

namespace OpenDxp\DataObject\ClassificationstoreDataMarshaller;

use OpenDxp\Marshaller\MarshallerInterface;

/**
 * @internal
 */
class BooleanSelect implements MarshallerInterface
{
    public function marshal(mixed $value, array $params = []): mixed
    {
        if ($value === true) {
            return ['value' => \OpenDxp\Model\DataObject\ClassDefinition\Data\BooleanSelect::YES_VALUE];
        }
        if ($value === false) {
            return ['value' => \OpenDxp\Model\DataObject\ClassDefinition\Data\BooleanSelect::NO_VALUE];
        }

        return null;
    }

    public function unmarshal(mixed $value, array $params = []): mixed
    {
        if (!is_array($value)) {
            return null;
        }
        if ($value['value'] == \OpenDxp\Model\DataObject\ClassDefinition\Data\BooleanSelect::YES_VALUE) {
            return true;
        }
        if ($value['value'] == \OpenDxp\Model\DataObject\ClassDefinition\Data\BooleanSelect::NO_VALUE) {
            return false;
        }

        return null;
    }
}
