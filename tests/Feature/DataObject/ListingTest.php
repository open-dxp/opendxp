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


namespace OpenDxp\Tests\Feature\DataObject;

use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Tests\Factory\UnittestFactory;

// The seven the fixture writes, plus the object root that every installation starts with.
const OBJECTS = 8;

beforeEach(function () {
    foreach ([10, 11, 42, 53, 65, 78, 85] as $seed) {
        UnittestFactory::createOne([
            'input' => 'content' . $seed,
            'number' => 99 + $seed,
            'firstname' => 'first?name ' . $seed,
            'lastname' => 'last:name ' . $seed,
        ]);
    }
});

it('counts the objects a condition matches', function (string $condition, array $parameters, int $expected) {

    $listing = new Unittest\Listing();
    $listing->setCondition($condition, $parameters);

    expect($listing->getTotalCount())->toBe($expected);
})->with([
    'a value written into the condition' => ['input = "content10"', [], 1],
    'two values written into the condition' => ['input = "content10" AND number = 109', [], 1],
    'a value passed in' => ['input = ?', ['content10'], 1],
    'two values passed in' => ['input = ? AND number = ?', ['content10', 109], 1],
    'a named value' => ['input = :param1', ['param1' => 'content10'], 1],
    'two named values, out of order' => ['input = :param1 AND number = :param2', ['param2' => 109, 'param1' => 'content10'], 1],
    'a list of values' => ['input IN (?)', [['content10', 'contentXX']], 1],
    'a list and a single value' => ['input IN (?) AND input = ?', [['content10', 'contentXX'], 'content10'], 1],
    'two lists and a single value' => ['input IN (?) AND input = ? AND number IN (?)', [['content10', 'contentXX'], 'content10', [109, 999]], 1],
]);

it('counts the objects the conditions it collected match', function (array $conditions, int $expected) {

    $listing = new Unittest\Listing();

    foreach ($conditions as $condition) {
        $listing->addConditionParam(...$condition);
    }

    expect($listing->getTotalCount())->toBe($expected);
})->with([
    'one condition' => [[['input = ?', 'content10']], 1],
    'two conditions' => [[['input = ?', 'content10'], ['number = ?', 109]], 1],
    'two conditions joined with or' => [[['input = ?', 'content10'], ['number = ?', 184, 'OR']], 2],
    'a question mark inside single quotes' => [[["firstname = 'first?name 11'"]], 1],
    'a colon inside single quotes' => [[["lastname = 'last:name 11'"]], 1],
    'a question mark inside double quotes' => [[['firstname = "first?name 11"']], 1],
    'a colon inside double quotes' => [[['lastname = "last:name 11"']], 1],
    'both inside double quotes' => [[['firstname = "first?name 11" AND lastname="last:name 11"']], 1],
    'a quoted question mark next to a value passed in' => [[["firstname = 'first?name 11' AND lastname = ?", 'last:name 11']], 1],
]);

it('counts again from the start when its condition changes', function () {

    $listing = new Unittest\Listing();
    $listing->setCondition('input IN (?)', [['content10', 'content11', 'content42']]);
    $listing->load();

    expect($listing->getCount())->toBe(3);

    $listing->setCondition('input IN (?)', [['content10', 'content11']]);

    expect($listing->getCount())->toBe(2);

    $listing->setLimit(1);

    expect($listing->getCount())->toBe(1);
});

it('counts every object there is', function () {
    expect((new DataObject\Listing())->getTotalCount())->toBe(OBJECTS);
});

it('counts only as many as the limit allows', function () {

    $listing = new DataObject\Listing();
    $listing->setLimit(3);
    $listing->setOffset(1);

    expect($listing->getCount())->toBe(3);
});

it('counts what is left behind the offset', function () {

    $listing = new DataObject\Listing();
    $listing->setLimit(10);
    $listing->setOffset(1);

    expect($listing->getCount())->toBe(OBJECTS - 1);
});

it('counts the same once the listing was loaded', function () {

    $listing = new DataObject\Listing();
    $listing->setLimit(10);
    $listing->setOffset(1);
    $listing->load();

    expect($listing->getCount())
        ->toBe(OBJECTS - 1)
        ->and($listing->getTotalCount())
        ->toBe(OBJECTS);
});
