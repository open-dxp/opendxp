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

namespace OpenDxp\Tests\Unit\Helper;

use League\Csv\EscapeFormula;

beforeEach(fn () => $this->formatter = new EscapeFormula("'", ['=', '-', '+', '@']));

it('puts a quote in front of a value a spreadsheet would read as a formula', function (string $value) {

    $escaped = $this->formatter->escapeRecord([$value]);

    expect($escaped[0])
        ->toBe("'" . $value)
        ->and($this->formatter->unescapeRecord($escaped)[0])
        ->toBe($value);
})->with(['=1+1', '-1+1', '+1+1', '@1+1']);

it('leaves a value a spreadsheet reads as text alone', function (string $value) {

    $escaped = $this->formatter->escapeRecord([$value]);

    expect($escaped[0])
        ->toBe($value)
        ->and($this->formatter->unescapeRecord($escaped)[0])
        ->toBe($value);
})->with(['test', 'test=test', 'test+test']);
