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

namespace OpenDxp\Model\Asset\MetaData\ClassDefinition\Data;

use Carbon\Carbon;
use OpenDxp\Tool\UserTimezone;
use Override;

class Date extends Data
{
    #[Override]
    public function getDataFromEditMode(mixed $data, array $params = []): mixed
    {
        return $this->normalize($data, $params);
    }

    public function normalize(mixed $value, array $params = []): mixed
    {
        if ($value && !is_numeric($value)) {
            return strtotime($value);
        }

        return $value;
    }

    #[Override]
    public function getVersionPreview(mixed $value, array $params = []): string
    {
        if (!$value) {
            return '';
        }

        $date = Carbon::createFromTimestamp((int) $value, date_default_timezone_get());

        return UserTimezone::applyTimezone($date)->format('Y-m-d');
    }
}
