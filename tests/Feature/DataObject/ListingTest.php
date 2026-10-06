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

use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Tests\Factory\UnittestFactory;

beforeEach(function () {
    UnittestFactory::createSequence(
        static function (): iterable {
            foreach ([10, 11, 42, 53, 65, 78, 85] as $seed) {
                yield [
                    'input' => sprintf('content%d', $seed),
                    'number' => 99 + $seed,
                    'firstname' => sprintf('first?name %d', $seed),
                    'lastname' => sprintf('last:name %d', $seed),
                ];
            }
        },
    );
});

it('counts the objects a condition matches', function (string $condition, array $parameters) {
    $listing = new Unittest\Listing();
    $listing->setCondition($condition, $parameters);

    $count = $listing->getTotalCount();

    expect($count)->toBe(1);
})->with([
    'a value written into the condition' => [
        'input = "content10"',
        [],
    ],
    'two values written into the condition' => [
        'input = "content10" AND number = 109',
        [],
    ],
    'a value passed in' => [
        'input = ?',
        ['content10'],
    ],
    'two values passed in' => [
        'input = ? AND number = ?',
        [
            'content10',
            109,
        ],
    ],
    'a named value' => [
        'input = :param1',
        ['param1' => 'content10'],
    ],
    'two named values out of order' => [
        'input = :param1 AND number = :param2',
        [
            'param2' => 109,
            'param1' => 'content10',
        ],
    ],
    'a list of values' => [
        'input IN (?)',
        [
            [
                'content10',
                'contentXX',
            ],
        ],
    ],
    'a list and a single value' => [
        'input IN (?) AND input = ?',
        [
            [
                'content10',
                'contentXX',
            ],
            'content10',
        ],
    ],
    'two lists and a single value' => [
        'input IN (?) AND input = ? AND number IN (?)',
        [
            [
                'content10',
                'contentXX',
            ],
            'content10',
            [
                109,
                999,
            ],
        ],
    ],
]);

it('counts the objects its collected conditions match', function (array $conditions, int $expected) {
    $listing = new Unittest\Listing();

    foreach ($conditions as $condition) {
        $listing->addConditionParam(...$condition);
    }

    $count = $listing->getTotalCount();

    expect($count)->toBe($expected);
})->with([
    'one condition' => [
        [
            ['input = ?', 'content10'],
        ],
        1,
    ],
    'two conditions' => [
        [
            ['input = ?', 'content10'],
            ['number = ?', 109],
        ],
        1,
    ],
    'two conditions joined with or' => [
        [
            ['input = ?', 'content10'],
            ['number = ?', 184, 'OR'],
        ],
        2,
    ],
    'a question mark inside single quotes' => [
        [
            ["firstname = 'first?name 11'"],
        ],
        1,
    ],
    'a colon inside single quotes' => [
        [
            ["lastname = 'last:name 11'"],
        ],
        1,
    ],
    'a question mark inside double quotes' => [
        [
            ['firstname = "first?name 11"'],
        ],
        1,
    ],
    'a colon inside double quotes' => [
        [
            ['lastname = "last:name 11"'],
        ],
        1,
    ],
    'both inside double quotes' => [
        [
            ['firstname = "first?name 11" AND lastname = "last:name 11"'],
        ],
        1,
    ],
    'a quoted question mark next to a value passed in' => [
        [
            ["firstname = 'first?name 11' AND lastname = ?", 'last:name 11'],
        ],
        1,
    ],
]);

it('counts again once its condition changes', function () {
    $listing = new Unittest\Listing();
    $listing->setCondition(
        'input IN (?)',
        [
            [
                'content10',
                'content11',
                'content42',
            ],
        ],
    );
    $listing->load();

    $listing->setCondition(
        'input IN (?)',
        [
            [
                'content10',
                'content11',
            ],
        ],
    );

    expect($listing->getCount())->toBe(2);
});

it('counts again once its limit changes', function () {
    $listing = new Unittest\Listing();
    $listing->setCondition(
        'input IN (?)',
        [
            [
                'content10',
                'content11',
                'content42',
            ],
        ],
    );
    $listing->load();

    $listing->setLimit(1);

    expect($listing->getCount())->toBe(1);
});
