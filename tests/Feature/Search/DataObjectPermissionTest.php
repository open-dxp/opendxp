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

namespace OpenDxp\Tests\Feature\Search;

use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\Tests\Factory\UnittestFactory;
use OpenDxp\Tests\Story\DataObjectPermissions;

use function Zenstruck\Foundry\faker;

beforeEach(fn () => $this->loadTree(DataObjectPermissions::class));

it('finds for each user only the objects that user may see', function (string $query, array $expected) {
    $users = array_keys($expected);

    $found = array_combine(
        $users,
        array_map(
            fn (string $user): array => $this->searchAs($user, 'object', $query)->paths,
            $users,
        ),
    );

    expect($found)->toEqualCanonicalizing($expected);
})->with([
    'a folder and what it holds' => [
        'bars',
        [
            'admin' => [
                '/permissionfoo/bars',
                '/permissionfoo/bars/hugo',
                '/permissionfoo/bars/userfolder',
                '/permissionfoo/bars/userfolder/usertestobject',
                '/permissionfoo/bars/groupfolder',
                '/permissionfoo/bars/groupfolder/grouptestobject',
            ],
            'Permissiontest1' => [
                '/permissionfoo/bars',
                '/permissionfoo/bars/userfolder',
                '/permissionfoo/bars/userfolder/usertestobject',
                '/permissionfoo/bars/groupfolder',
                '/permissionfoo/bars/groupfolder/grouptestobject',
            ],
            'Permissiontest2' => [
                '/permissionfoo/bars',
                '/permissionfoo/bars/userfolder',
                '/permissionfoo/bars/userfolder/usertestobject',
            ],
        ],
    ],
    'an object only the administrator may see' => [
        'hugo',
        [
            'admin' => ['/permissionfoo/bars/hugo'],
            'Permissiontest1' => [],
            'Permissiontest2' => [],
        ],
    ],
    'a hidden object' => [
        'hiddenobject',
        [
            'admin' => ['/permissionbar/foo/hiddenobject'],
            'Permissiontest1' => [],
            'Permissiontest2' => [],
        ],
    ],
]);

it('counts only the objects a user may see in the total', function () {
    $answer = $this->searchAs('Permissiontest1', 'object', 'bars');

    expect($answer->total)->toBe(5);
});

it('fills a limited page only with objects a user may see', function () {
    $folder = index(DataObjectFolderFactory::createOne());
    $hidden = UnittestFactory::new()
        ->withParent($folder)
        ->many(5)
        ->create(static function (): array {
            $name = sprintf('manyelement %s', faker()->unique()->slug());

            return [
                'key' => $name,
                'input' => $name,
            ];
        });
    array_walk($hidden, index(...));
    $visible = UnittestFactory::new()
        ->withParent($folder)
        ->create([
            'key' => 'manyelement visible',
            'input' => 'manyelement visible',
        ]);
    index($visible);
    $user = UserFactory::new()
        ->withPermissions('objects')
        ->withObjectWorkspace($visible, 'list', 'view')
        ->create();
    $this->forgetAfterwards($folder, $user);

    $answer = $this->searchPageAs($user->getName(), 'object', 'manyelement', 5);

    expect($answer->paths)->toBe([$visible->getRealFullPath()]);
});
