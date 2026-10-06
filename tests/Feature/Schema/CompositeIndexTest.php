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

namespace OpenDxp\Tests\Feature\Schema;

use OpenDxp\Db;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Unittest;

/**
 * @return list<string>
 */
function queryTableIndexes(): array
{
    $query = sprintf('SHOW INDEX FROM `object_query_%s`', Unittest::classId());
    $indexes = Db::get()->fetchAllAssociative($query);

    return array_column($indexes, 'Key_name');
}

afterEach(function () {
    $definition = ClassDefinition::getById(Unittest::classId());
    $definition->setCompositeIndices([]);
    $definition->save();
});

it('writes the composite index a class definition names', function () {
    $definition = ClassDefinition::getById(Unittest::classId());
    $definition->setCompositeIndices([
        [
            'index_key' => 'mycomposite',
            'index_type' => 'query',
            'index_columns' => [
                'slider',
                'number',
            ],
        ],
    ]);

    $definition->save();

    expect(queryTableIndexes())->toContain('c_mycomposite');
});
