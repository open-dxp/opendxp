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

namespace OpenDxp\DataObject\BlockDataMarshaller;

use OpenDxp\Marshaller\MarshallerInterface;

/**
 * @internal
 */
class Geopolygon implements MarshallerInterface
{
    public function marshal(mixed $value, array $params = []): mixed
    {
        if (is_array($value)) {
            $resultItems = [];
            foreach ($value as $p) {
                $resultItems[] = [$p['latitude'], $p['longitude']];
            }

            return ['value' => json_encode($resultItems)];
        }

        return null;
    }

    public function unmarshal(mixed $value, array $params = []): mixed
    {
        if ($value['value'] ?? null) {
            $value = json_decode($value['value'], true);
            $result = [];

            if (is_array($value)) {
                foreach ($value as $point) {
                    $result[] = [
                        'latitude' => $point[0],
                        'longitude' => $point[1],
                    ];
                }
            }

            return $result;
        }

        return null;
    }
}
