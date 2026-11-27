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

namespace OpenDxp\Tests\Feature\ClassificationStore;

use Doctrine\DBAL\Query\QueryBuilder;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Service;
use OpenDxp\Model\DataObject\TestClassification;
use OpenDxp\Tests\Factory\TestClassificationFactory;

it('filters by checkbox key of classification store grid column', function () {
    $checked = TestClassificationFactory::new()
        ->withClassificationValues('specifications', storeGroup('values'), ['checkbox' => true])
        ->create();
    TestClassificationFactory::new()
        ->withClassificationValues('specifications', storeGroup('values'), ['checkbox' => false])
        ->create();

    $class = ClassDefinition::getByName('TestClassification');
    $column = Service::findGridColumn($class, sprintf(
        '~classificationstore~specifications~%d-%d',
        storeGroup('values')->getId(),
        storeKey('checkbox')->getId(),
    ));
    $listing = new TestClassification\Listing();
    $listing->onCreateQueryBuilder(static function (QueryBuilder $select) use ($column, $class, $listing): void {
        $column->addToQuery($select, $class, $listing->getDao()->getTableName(), 'gridcolumn_checkbox');
        $select->andHaving($column->getFilterCondition(1, '=', 'gridcolumn_checkbox'));
    });

    expect($listing->loadIdList())->toBe([$checked->getId()]);
});
