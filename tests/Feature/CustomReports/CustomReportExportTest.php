<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Feature\CustomReports;

use OpenDxp\Bundle\AdminBundle\Test\GridExport\GridExports;
use OpenDxp\Test\Factory\CustomReportFactory;
use OpenDxp\Test\Factory\UserFactory;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

beforeEach(function () {
    $this->admin = UserFactory::new()
        ->admin()
        ->create();
});

it('exports the rows of a report', function () {
    $report = CustomReportFactory::new()
        ->selecting("SELECT 'Ada' AS person UNION SELECT 'Grace'", ['person'])
        ->create();

    $file = GridExports::export($this->admin, 'custom-reports', ['name' => $report->getName()]);

    expect($file)
        ->column('person')
        ->toBe(['Ada', 'Grace']);
});

it('exports the rows in the sorting of the grid', function () {
    $report = CustomReportFactory::new()
        ->selecting("SELECT 'Ada' AS person UNION SELECT 'Grace'", ['person'])
        ->create();
    $parameters = [
        'name' => $report->getName(),
        'sort' => json_encode([['property' => 'person', 'direction' => 'DESC']]),
    ];

    $file = GridExports::export($this->admin, 'custom-reports', $parameters);

    expect($file)
        ->column('person')
        ->toBe(['Grace', 'Ada']);
});

it('exports only the columns that the report marks for the export', function () {
    $report = CustomReportFactory::new()
        ->selecting("SELECT 'Ada' AS person, 'secret' AS password", ['person'])
        ->create();

    $file = GridExports::export($this->admin, 'custom-reports', ['name' => $report->getName()]);

    expect($file)
        ->header()
        ->toBe(['person']);
});

it('refuses a report that is not shared with the user', function () {
    $report = CustomReportFactory::new()
        ->selecting("SELECT 'Ada' AS person", ['person'])
        ->create(['shareGlobally' => false]);
    $user = UserFactory::new()
        ->withPermissions('reports')
        ->create();

    expect(fn () => GridExports::export($user, 'custom-reports', ['name' => $report->getName()]))
        ->toThrow(AccessDeniedException::class);
});
