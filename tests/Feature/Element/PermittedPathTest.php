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

namespace OpenDxp\Tests\Feature\Element;

use Closure;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use OpenDxp\Test\Factory\AbstractElementFactory;
use OpenDxp\Test\Factory\AbstractUserRoleFactory;
use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Test\Factory\DocumentFolderFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\Test\Factory\UserRoleFactory;

/**
 * Two workspaces may name the same path. A folder that exists is therefore found again instead of created twice.
 */
function folderAt(AbstractElementFactory $folders, string $path): ElementInterface
{
    $class = $folders::class();
    $existing = $class::getByPath($path);

    if ($existing !== null) {
        return $existing;
    }

    $folder = $folders->with(['key' => basename($path)]);
    $parentPath = dirname($path);

    if ($parentPath !== '/') {
        $folder = $folder->withParent(folderAt($folders, $parentPath));
    }

    return $folder->create();
}

/**
 * A workspace without permissions closes its path. A workspace with the list permission opens it.
 */
dataset('workspaces', [
    'assets' => [
        'asset',
        fn (AbstractUserRoleFactory $owner, string $path, string ...$permissions) => $owner->withAssetWorkspace(
            folderAt(AssetFolderFactory::new(), $path),
            ...$permissions,
        ),
    ],
    'documents' => [
        'document',
        fn (AbstractUserRoleFactory $owner, string $path, string ...$permissions) => $owner->withDocumentWorkspace(
            folderAt(DocumentFolderFactory::new(), $path),
            ...$permissions,
        ),
    ],
    'objects' => [
        'object',
        fn (AbstractUserRoleFactory $owner, string $path, string ...$permissions) => $owner->withObjectWorkspace(
            folderAt(DataObjectFolderFactory::new(), $path),
            ...$permissions,
        ),
    ],
]);

dataset('element type names', [
    'assets' => 'asset',
    'documents' => 'document',
    'objects' => 'object',
]);

it('leaves everything open to an administrator', function (string $type) {
    $admin = UserFactory::new()
        ->admin()
        ->create();

    $paths = Service::findForbiddenPaths($type, $admin);

    expect($paths['forbidden'])
        ->toBe([])
        ->and($paths['allowed'])
        ->toBe(['/']);
})->with('element type names');

it('closes the root to a user without a workspace', function (string $type) {
    $user = UserFactory::createOne();

    $paths = Service::findForbiddenPaths($type, $user);

    expect($paths['forbidden'])
        ->toHaveKey('/', [])
        ->and($paths['allowed'])
        ->toBe([]);
})->with('element type names');

it('opens a path the user may list', function (string $type, Closure $withWorkspace) {
    $owner = UserFactory::new();
    $owner = $withWorkspace($owner, '/foo', 'list');
    $user = $owner->create();

    $paths = Service::findForbiddenPaths($type, $user);

    expect($paths['allowed'])
        ->toContain('/foo')
        ->and($paths['forbidden'])
        ->not->toHaveKey('/foo');
})->with('workspaces');

it('closes a path the user may not list', function (string $type, Closure $withWorkspace) {
    $owner = UserFactory::new();
    $owner = $withWorkspace($owner, '/foo');
    $user = $owner->create();

    $paths = Service::findForbiddenPaths($type, $user);

    expect($paths['forbidden'])->toHaveKey('/foo', []);
})->with('workspaces');

it('opens a child of a closed path', function (string $type, Closure $withWorkspace) {
    $owner = UserFactory::new();
    $owner = $withWorkspace($owner, '/foo');
    $owner = $withWorkspace($owner, '/foo/bar', 'list');
    $user = $owner->create();

    $paths = Service::findForbiddenPaths($type, $user);

    expect($paths['forbidden']['/foo'])
        ->toContain('/foo/bar')
        ->and($paths['allowed'])
        ->toContain('/foo/bar');
})->with('workspaces');

it('opens every path the user may list', function (string $type, Closure $withWorkspace) {
    $owner = UserFactory::new();
    $owner = $withWorkspace($owner, '/a', 'list');
    $owner = $withWorkspace($owner, '/b', 'list');
    $owner = $withWorkspace($owner, '/c', 'list');
    $user = $owner->create();

    $paths = Service::findForbiddenPaths($type, $user);

    expect($paths['allowed'])
        ->toContain('/a', '/b', '/c')
        ->and($paths['forbidden'])
        ->toBeEmpty();
})->with('workspaces');

it('closes a child of an open path', function (string $type, Closure $withWorkspace) {
    $owner = UserFactory::new();
    $owner = $withWorkspace($owner, '/foo', 'list');
    $owner = $withWorkspace($owner, '/foo/secret');
    $user = $owner->create();

    $paths = Service::findForbiddenPaths($type, $user);

    expect($paths['allowed'])
        ->toContain('/foo')
        ->and($paths['forbidden'])
        ->toHaveKey('/foo/secret', []);
})->with('workspaces');

it('lists only its own open children under a closed path', function (string $type, Closure $withWorkspace) {
    $owner = UserFactory::new();
    $owner = $withWorkspace($owner, '/a');
    $owner = $withWorkspace($owner, '/a/child', 'list');
    $owner = $withWorkspace($owner, '/b', 'list');
    $user = $owner->create();

    $paths = Service::findForbiddenPaths($type, $user);

    expect($paths['forbidden']['/a'])
        ->toContain('/a/child')
        ->not->toContain('/b')
        ->and($paths['allowed'])
        ->toContain('/b');
})->with('workspaces');

it('lists an open path under each closed ancestor', function (string $type, Closure $withWorkspace) {
    $owner = UserFactory::new();
    $owner = $withWorkspace($owner, '/root');
    $owner = $withWorkspace($owner, '/root/a');
    $owner = $withWorkspace($owner, '/root/a/b', 'list');
    $user = $owner->create();

    $paths = Service::findForbiddenPaths($type, $user);

    expect($paths['forbidden']['/root/a'])
        ->toContain('/root/a/b')
        ->and($paths['forbidden']['/root'])
        ->toContain('/root/a/b');
})->with('workspaces');

it('closes a grandchild below an open child of a closed path', function (string $type, Closure $withWorkspace) {
    $owner = UserFactory::new();
    $owner = $withWorkspace($owner, '/foo');
    $owner = $withWorkspace($owner, '/foo/bar', 'list');
    $owner = $withWorkspace($owner, '/foo/bar/baz');
    $user = $owner->create();

    $paths = Service::findForbiddenPaths($type, $user);

    expect($paths['forbidden']['/foo'])
        ->toContain('/foo/bar')
        ->and($paths['allowed'])
        ->toContain('/foo/bar')
        ->and($paths['forbidden'])
        ->toHaveKey('/foo/bar/baz', []);
})->with('workspaces');

it('does not treat a path with the same ending as a child', function (string $type, Closure $withWorkspace) {
    $owner = UserFactory::new();
    $owner = $withWorkspace($owner, '/foo');
    $owner = $withWorkspace($owner, '/baz/foo', 'list');
    $user = $owner->create();

    $paths = Service::findForbiddenPaths($type, $user);

    expect($paths['forbidden']['/foo'])
        ->not->toContain('/baz/foo')
        ->and($paths['allowed'])
        ->toContain('/baz/foo');
})->with('workspaces');

it('opens a path for a user through a role', function (string $type, Closure $withWorkspace) {
    $roleOwner = UserRoleFactory::new();
    $roleOwner = $withWorkspace($roleOwner, '/role-path', 'list');
    $user = UserFactory::new()
        ->withRoles($roleOwner->create())
        ->create();

    $paths = Service::findForbiddenPaths($type, $user);

    expect($paths['allowed'])->toContain('/role-path');
})->with('workspaces');

it('closes a path for the user although a role opens it', function (string $type, Closure $withWorkspace) {
    $roleOwner = UserRoleFactory::new();
    $roleOwner = $withWorkspace($roleOwner, '/contested', 'list');
    $owner = UserFactory::new()
        ->withRoles($roleOwner->create());
    $owner = $withWorkspace($owner, '/contested');
    $user = $owner->create();

    $paths = Service::findForbiddenPaths($type, $user);

    expect($paths['forbidden'])
        ->toHaveKey('/contested')
        ->and($paths['allowed'])
        ->not->toContain('/contested');
})->with('workspaces');

it('opens a path for the user although a role closes it', function (string $type, Closure $withWorkspace) {
    $roleOwner = UserRoleFactory::new();
    $roleOwner = $withWorkspace($roleOwner, '/contested');
    $owner = UserFactory::new()
        ->withRoles($roleOwner->create());
    $owner = $withWorkspace($owner, '/contested', 'list');
    $user = $owner->create();

    $paths = Service::findForbiddenPaths($type, $user);

    expect($paths['allowed'])
        ->toContain('/contested')
        ->and($paths['forbidden'])
        ->not->toHaveKey('/contested');
})->with('workspaces');

it('opens a path that one role opens and another role closes', function (string $type, Closure $withWorkspace) {
    $closing = UserRoleFactory::new();
    $closing = $withWorkspace($closing, '/shared');
    $opening = UserRoleFactory::new();
    $opening = $withWorkspace($opening, '/shared', 'list');
    $user = UserFactory::new()
        ->withRoles(
            $closing->create(),
            $opening->create(),
        )
        ->create();

    $paths = Service::findForbiddenPaths($type, $user);

    expect($paths['allowed'])->toContain('/shared');
})->with('workspaces');
