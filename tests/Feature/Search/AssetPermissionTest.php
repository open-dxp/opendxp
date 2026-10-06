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

use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\Tests\Story\AssetPermissions;

use function Zenstruck\Foundry\faker;

beforeEach(fn () => $this->loadTree(AssetPermissions::class));

it('finds for each user only the assets that user may see', function (string $query, array $expected) {
    $users = array_keys($expected);

    $found = array_combine(
        $users,
        array_map(
            fn (string $user): array => $this->searchAs($user, 'asset', $query)->paths,
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
                '/permissionfoo/bars/hugo.gif',
                '/permissionfoo/bars/userfolder',
                '/permissionfoo/bars/userfolder/usertestobject.gif',
                '/permissionfoo/bars/groupfolder',
                '/permissionfoo/bars/groupfolder/grouptestobject.gif',
            ],
            'Permissiontest1' => [
                '/permissionfoo/bars',
                '/permissionfoo/bars/userfolder',
                '/permissionfoo/bars/userfolder/usertestobject.gif',
                '/permissionfoo/bars/groupfolder',
                '/permissionfoo/bars/groupfolder/grouptestobject.gif',
            ],
            'Permissiontest2' => [
                '/permissionfoo/bars',
                '/permissionfoo/bars/userfolder',
                '/permissionfoo/bars/userfolder/usertestobject.gif',
            ],
        ],
    ],
    'an asset only the administrator may see' => [
        'hugo',
        [
            'admin' => ['/permissionfoo/bars/hugo.gif'],
            'Permissiontest1' => [],
            'Permissiontest2' => [],
        ],
    ],
    'a hidden asset' => [
        'hiddenobject',
        [
            'admin' => ['/permissionbar/foo/hiddenobject.gif'],
            'Permissiontest1' => [],
            'Permissiontest2' => [],
        ],
    ],
]);

it('counts only the assets a user may see in the total', function () {
    $answer = $this->searchAs('Permissiontest1', 'asset', 'bars');

    expect($answer->total)->toBe(5);
});

it('fills a limited page only with assets a user may see', function () {
    $folder = index(AssetFolderFactory::createOne());
    $hidden = AssetImageFactory::new()
        ->withParent($folder)
        ->many(5)
        ->create(static fn (): array => [
            'key' => sprintf('manyelement %s.gif', faker()->unique()->slug()),
        ]);
    array_walk($hidden, index(...));
    $visible = AssetImageFactory::new()
        ->withParent($folder)
        ->create(['key' => 'manyelement visible.gif']);
    index($visible);
    $user = UserFactory::new()
        ->withPermissions('assets')
        ->withAssetWorkspace($visible, 'list', 'view')
        ->create();
    $this->forgetAfterwards($folder, $user);

    $answer = $this->searchPageAs($user->getName(), 'asset', 'manyelement', 5);

    expect($answer->paths)->toBe([$visible->getRealFullPath()]);
});
