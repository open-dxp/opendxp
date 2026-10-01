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

use OpenDxp\Model\Element\Service;
use OpenDxp\Test\Factory\RoleFactory;
use OpenDxp\Test\Factory\UserFactory;

beforeEach(function () {
    $this->admin = UserFactory::new()->admin()->create();
    $this->user = UserFactory::createOne();
});

describe('the paths a user may see', function () {
    it('leaves everything open to an administrator', function (string $type) {

        $paths = Service::findForbiddenPaths($type, $this->admin);

        expect($paths['forbidden'])
            ->toBe([])
            ->and($paths['allowed'])
            ->toBe(['/']);
    });

    it('forbids the root to a user without a single workspace', function (string $type) {

        $paths = Service::findForbiddenPaths($type, $this->user);

        expect($paths['forbidden'])
            ->toHaveKey('/', [])
            ->and($paths['allowed'])
            ->toBe([]);
    });

    it('opens a path the user is listed for', function (string $type) {

        allowPath($type, $this->user->getId(), '/foo');
        $paths = Service::findForbiddenPaths($type, $this->user);

        expect($paths['allowed'])
            ->toContain('/foo')
            ->and($paths['forbidden'])
            ->not->toHaveKey('/foo');
    });

    it('closes a path the user is not listed for', function (string $type) {

        forbidPath($type, $this->user->getId(), '/foo');
        $paths = Service::findForbiddenPaths($type, $this->user);

        expect($paths['forbidden'])->toHaveKey('/foo', []);
    });

    it('opens a child of a closed path', function (string $type) {

        forbidPath($type, $this->user->getId(), '/foo');
        allowPath($type, $this->user->getId(), '/foo/bar');
        $paths = Service::findForbiddenPaths($type, $this->user);

        expect($paths['forbidden']['/foo'])
            ->toContain('/foo/bar')
            ->and($paths['allowed'])
            ->toContain('/foo/bar');
    });

    it('opens every path the user is listed for', function (string $type) {

        foreach (['/a', '/b', '/c'] as $path) {
            allowPath($type, $this->user->getId(), $path);
        }

        $paths = Service::findForbiddenPaths($type, $this->user);

        expect($paths['allowed'])
            ->toContain('/a', '/b', '/c')
            ->and($paths['forbidden'])
            ->toBeEmpty();
    });

    it('closes a child of an open path', function (string $type) {

        allowPath($type, $this->user->getId(), '/foo');
        forbidPath($type, $this->user->getId(), '/foo/secret');
        $paths = Service::findForbiddenPaths($type, $this->user);

        expect($paths['allowed'])
            ->toContain('/foo')
            ->and($paths['forbidden'])
            ->toHaveKey('/foo/secret', []);
    });

    it('keeps a closed path out of the way of its siblings', function (string $type) {

        forbidPath($type, $this->user->getId(), '/a');
        allowPath($type, $this->user->getId(), '/a/child');
        allowPath($type, $this->user->getId(), '/b');
        $paths = Service::findForbiddenPaths($type, $this->user);

        expect($paths['forbidden']['/a'])
            ->toContain('/a/child')
            ->not->toContain('/b')
            ->and($paths['allowed'])
            ->toContain('/b');
    });

    it('reaches an open path through several closed ancestors', function (string $type) {

        forbidPath($type, $this->user->getId(), '/root');
        forbidPath($type, $this->user->getId(), '/root/a');
        allowPath($type, $this->user->getId(), '/root/a/b');
        $paths = Service::findForbiddenPaths($type, $this->user);

        expect($paths['forbidden']['/root/a'])
            ->toContain('/root/a/b')
            ->and($paths['forbidden']['/root'])
            ->toContain('/root/a/b');
    });

    it('closes a grandchild below an open child of a closed path', function (string $type) {

        forbidPath($type, $this->user->getId(), '/foo');
        allowPath($type, $this->user->getId(), '/foo/bar');
        forbidPath($type, $this->user->getId(), '/foo/bar/baz');
        $paths = Service::findForbiddenPaths($type, $this->user);

        expect($paths['forbidden']['/foo'])
            ->toContain('/foo/bar')
            ->and($paths['allowed'])
            ->toContain('/foo/bar')
            ->and($paths['forbidden'])
            ->toHaveKey('/foo/bar/baz', []);
    });

    it('takes a path that only ends the same for a sibling, not a child', function (string $type) {

        forbidPath($type, $this->user->getId(), '/foo');
        allowPath($type, $this->user->getId(), '/baz/foo');
        $paths = Service::findForbiddenPaths($type, $this->user);

        expect($paths['forbidden']['/foo'])
            ->not->toContain('/baz/foo')
            ->and($paths['allowed'])
            ->toContain('/baz/foo');
    });
})->with('element types');

describe('a path a role opens', function () {
    it('reaches the user who carries the role', function (string $type) {

        $role = RoleFactory::createOne();
        $this->user->setRoles([$role->getId()]);
        $this->user->save();

        allowPath($type, $role->getId(), '/role-path');

        expect(Service::findForbiddenPaths($type, $this->user)['allowed'])->toContain('/role-path');
    });

    it('loses against the user being closed for it', function (string $type) {

        $role = RoleFactory::createOne();
        $this->user->setRoles([$role->getId()]);
        $this->user->save();

        $element = allowPath($type, $role->getId(), '/contested');
        forbidPath($type, $this->user->getId(), '/contested', $element);
        $paths = Service::findForbiddenPaths($type, $this->user);

        expect($paths['forbidden'])
            ->toHaveKey('/contested')
            ->and($paths['allowed'])
            ->not->toContain('/contested');
    });

    it('loses against the user being open for it', function (string $type) {

        $role = RoleFactory::createOne();
        $this->user->setRoles([$role->getId()]);
        $this->user->save();

        $element = forbidPath($type, $role->getId(), '/contested');
        allowPath($type, $this->user->getId(), '/contested', $element);
        $paths = Service::findForbiddenPaths($type, $this->user);

        expect($paths['allowed'])
            ->toContain('/contested')
            ->and($paths['forbidden'])
            ->not->toHaveKey('/contested');
    });

    it('wins over another role that closes the same path', function (string $type) {

        $closing = RoleFactory::createOne();
        $opening = RoleFactory::createOne();
        $this->user->setRoles([$closing->getId(), $opening->getId()]);
        $this->user->save();

        $element = forbidPath($type, $closing->getId(), '/shared');
        allowPath($type, $opening->getId(), '/shared', $element);

        expect(Service::findForbiddenPaths($type, $this->user)['allowed'])->toContain('/shared');
    });
})->with('element types');

it('names a user without a role as having none', function () {
    expect($this->user->getRoles())->toBeEmpty();
});
