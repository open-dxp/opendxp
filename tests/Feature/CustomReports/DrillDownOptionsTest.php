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

namespace OpenDxp\Tests\Feature\CustomReports;

use OpenDxp\Db;
use OpenDxp\Test\Factory\CustomReportFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;

beforeEach(function () {
    $this->user = UserFactory::new()
        ->withPermissions('reports')
        ->create(['name' => 'reader']);
    $this->report = CustomReportFactory::new()
        ->withSqlDataSource("SELECT 'Ada' AS person UNION SELECT 'Grace'", ['person'])
        ->create();
});

it('lists the values of a column as options', function () {
    $browser = Browser::actingAs($this->user)
        ->post('/admin/bundle/customreports/custom-report/drill-down-options', [
            'body' => [
                'name' => $this->report->getName(),
                'field' => 'person',
            ],
        ]);

    expect(json_decode($browser->content(), true, flags: JSON_THROW_ON_ERROR)['data'])
        ->toBe([
            ['name' => 'empty', 'value' => null],
            ['name' => 'Ada', 'value' => 'Ada'],
            ['name' => 'Grace', 'value' => 'Grace'],
        ]);
});

it('reads the field as the name of a column', function () {
    $field = sprintf(
        "person` FROM (SELECT 1 AS person) AS s; UPDATE users SET name = 'intruder' WHERE id = %d; SELECT `person",
        $this->user->getId(),
    );

    Browser::actingAs($this->user)
        ->post('/admin/bundle/customreports/custom-report/drill-down-options', [
            'body' => [
                'name' => $this->report->getName(),
                'field' => $field,
            ],
        ]);

    expect(Db::get()->fetchOne('SELECT name FROM users WHERE id = ?', [$this->user->getId()]))
        ->toBe('reader');
});
