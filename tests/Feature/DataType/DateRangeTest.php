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

it('reads a range of dates back out of the version it was written into', function () {

    $object = UnittestFactory::createOne([
        'dateRange' => new CarbonPeriod('2018-04-21', '3 days', '2018-04-27'),
    ]);

    $written = $object->getDateRange();
    $fromVersion = $object->getLatestVersion(includingPublished: true)->loadData(true)->getDateRange();

    expect($fromVersion)
        ->not->toBe($written)
        ->and($fromVersion->getStartDate())
        ->toEqual($written->getStartDate())
        ->and($fromVersion->getEndDate())
        ->toEqual($written->getEndDate())
        ->and($fromVersion->getRecurrences())
        ->toEqual($written->getRecurrences())
        ->and($fromVersion->isStartExcluded())
        ->toBe($written->isStartExcluded())
        ->and($fromVersion->isEndExcluded())
        ->toBe($written->isEndExcluded());
});
