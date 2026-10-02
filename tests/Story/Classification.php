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


namespace OpenDxp\Tests\Story;

use OpenDxp\Test\Factory\ClassificationGroupFactory;
use OpenDxp\Test\Factory\ClassificationKeyFactory;
use OpenDxp\Test\Factory\ClassificationKeyGroupRelationFactory;
use OpenDxp\Test\Factory\ClassificationStoreFactory;
use Zenstruck\Foundry\Story;

/**
 * The classification store the test classes carry, with one group and two text keys in it.
 */
final class Classification extends Story
{
    public function build(): void
    {
        $store = ClassificationStoreFactory::createOne(['name' => 'teststore']);

        $group = ClassificationGroupFactory::createOne(['storeId' => $store->getId(), 'name' => 'group1']);

        // The store knows the keys as key1 and key2, the tests reach them as the first and the second.
        foreach (['first' => 'key1', 'second' => 'key2'] as $position => $name) {
            $key = ClassificationKeyFactory::createOne(['storeId' => $store->getId(), 'name' => $name]);

            ClassificationKeyGroupRelationFactory::createOne([
                'groupId' => $group->getId(),
                'keyId' => $key->getId(),
                'sorter' => $name === 'key1' ? 1 : 2,
            ]);

            self::addState($position, $key);
        }

        self::addState('store', $store);
        self::addState('group', $group);
    }
}
