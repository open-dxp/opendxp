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


namespace OpenDxp\Tests\Feature\Tool;

use OpenDxp\Model\User;
use OpenDxp\Security\User\User as SecurityUser;
use OpenDxp\Test\Factory\DocumentFolderFactory;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\Tool\Authentication;
use ReflectionMethod;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

function unserializedSafely(string $payload): mixed
{
    return (new ReflectionMethod(Authentication::class, 'safelyUnserialize'))->invoke(null, $payload);
}

it('accepts the token of a user whose access is restricted to a workspace', function () {

    $objects = DataObjectFolderFactory::createOne();
    $documents = DocumentFolderFactory::createOne();

    // A superadmin carries no workspace, so the restrictions would never be unserialized.
    $user = UserFactory::createOne([
        'permissions' => ['objects', 'documents'],
        'workspacesObject' => [
            (new User\Workspace\DataObject())->setValues([
                'cId' => $objects->getId(),
                'cPath' => $objects->getFullpath(),
                'list' => true,
                'view' => true,
            ]),
        ],
        'workspacesDocument' => [
            (new User\Workspace\Document())->setValues([
                'cId' => $documents->getId(),
                'cPath' => $documents->getFullpath(),
                'list' => true,
                'view' => true,
            ]),
        ],
    ]);

    $securityUser = new SecurityUser($user);
    $token = new PostAuthenticationToken($securityUser, 'opendxp_admin', $securityUser->getRoles());

    $restored = unserializedSafely(serialize($token));

    expect($restored)
        ->toBeInstanceOf(TokenInterface::class)
        ->and($restored->getUser())
        ->toBeInstanceOf(SecurityUser::class)
        ->and($restored->getUser()->getUser()->getId())
        ->toBe($user->getId());
});

it('hands back nothing instead of breaking', function (string $payload) {
    expect(unserializedSafely($payload))->toBeNull();
})->with([
    'for a payload that is not serialized data at all' => ['this is not a valid serialized payload at all'],
    'for a payload naming a class that does not exist' => ['O:34:"Totally\Nonexistent\FakeClassXyz":0:{}'],
]);
