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

namespace OpenDxp\Tests\Unit\Model\Element;

use OpenDxp\Model\Element\Service;

it('escapes a value a spreadsheet would read as a formula', function (string $value, string $escaped) {
    $record = Service::escapeCsvRecord([$value]);

    expect($record)->toBe([$escaped]);
})->with([
    'an equals sign' => ['=1+1', "'=1+1"],
    'a minus sign' => ['-1+1', "'-1+1"],
    'a plus sign' => ['+1+1', "'+1+1"],
    'an at sign' => ['@1+1', "'@1+1"],
]);

it('leaves a value alone that a spreadsheet reads as text', function (string $value) {
    $record = Service::escapeCsvRecord([$value]);

    expect($record)->toBe([$value]);
})->with([
    'plain text' => ['test'],
    'an equals sign inside the text' => ['test=test'],
    'a plus sign inside the text' => ['test+test'],
]);

it('restores an escaped value', function (string $escaped, string $value) {
    $record = Service::unEscapeCsvRecord([$escaped]);

    expect($record)->toBe([$value]);
})->with([
    'an equals sign' => ["'=1+1", '=1+1'],
    'a minus sign' => ["'-1+1", '-1+1'],
    'a plus sign' => ["'+1+1", '+1+1'],
    'an at sign' => ["'@1+1", '@1+1'],
]);
