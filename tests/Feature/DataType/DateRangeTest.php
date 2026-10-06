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

namespace OpenDxp\Tests\Feature\DataType;

use Carbon\CarbonPeriod;
use OpenDxp\Tests\Factory\UnittestFactory;

it('reads a range of dates back out of its version', function () {
    $range = new CarbonPeriod('2018-04-21', '3 days', '2018-04-27');

    $object = UnittestFactory::createOne(['dateRange' => $range]);
    $version = $object->getLatestVersion(includingPublished: true);
    $versioned = $version->loadData(renewReferences: true)->getDateRange();

    expect($versioned)
        ->not->toBe($range)
        ->getStartDate()
        ->toEqual($range->getStartDate())
        ->getEndDate()
        ->toEqual($range->getEndDate())
        ->getRecurrences()
        ->toEqual($range->getRecurrences())
        ->isStartExcluded()
        ->toBe($range->isStartExcluded())
        ->isEndExcluded()
        ->toBe($range->isEndExcluded());
});
