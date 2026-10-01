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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tests\Unit\Helper;

use League\Csv\EscapeFormula;
use OpenDxp\Tests\Support\Test\TestCase;

class CsvFormulaTest extends TestCase
{
    public function testUnEscapeFormula(): void
    {
        $formatter = new EscapeFormula("'", ['=', '-', '+', '@']);

        $escapedRow = $formatter->escapeRecord(['=1+1']);
        $this->assertEquals("'=1+1", $escapedRow[0]);
        $this->assertEquals('=1+1', $formatter->unescapeRecord($escapedRow)[0]);

        $escapedRow = $formatter->escapeRecord(['-1+1']);
        $this->assertEquals("'-1+1", $escapedRow[0]);
        $this->assertEquals('-1+1', $formatter->unescapeRecord($escapedRow)[0]);

        $escapedRow = $formatter->escapeRecord(['+1+1']);
        $this->assertEquals("'+1+1", $escapedRow[0]);
        $this->assertEquals('+1+1', $formatter->unescapeRecord($escapedRow)[0]);

        $escapedRow = $formatter->escapeRecord(['@1+1']);
        $this->assertEquals("'@1+1", $escapedRow[0]);
        $this->assertEquals('@1+1', $formatter->unescapeRecord($escapedRow)[0]);

        // There should be no escape. So the string should be returned as is.
        $escapedRow = $formatter->escapeRecord(['test']);
        $this->assertEquals('test', $escapedRow[0]);
        $this->assertEquals('test', $formatter->unescapeRecord($escapedRow)[0]);

        $testString = 'test=test';
        $escapedRow = $formatter->escapeRecord([$testString]);
        $this->assertEquals($testString, $escapedRow[0]);
        $this->assertEquals($testString, $formatter->unescapeRecord($escapedRow)[0]);

        $testString = 'test+test';
        $escapedRow = $formatter->escapeRecord([$testString]);
        $this->assertEquals($testString, $escapedRow[0]);
        $this->assertEquals($testString, $formatter->unescapeRecord($escapedRow)[0]);
    }
}
