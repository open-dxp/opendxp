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

namespace OpenDxp\Tests\Model\Tool;

use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;
use OpenDxp\Model\User;
use OpenDxp\Security\User\User as SecurityUser;
use OpenDxp\Tests\Support\Test\ModelTestCase;
use OpenDxp\Tests\Support\Util\TestHelper;
use OpenDxp\Tool\Authentication;
use ReflectionMethod;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

class AuthenticationTest extends ModelTestCase
{
    private DataObject\Folder $objectFolder;

    private Document\Folder $documentFolder;

    private User $restrictedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectFolder = TestHelper::createObjectFolder('authtest');
        $this->documentFolder = TestHelper::createDocumentFolder('authtest');

        // Not a superadmin: real workspace restrictions, on purpose
        $this->restrictedUser = new User();
        $this->restrictedUser->setName('authtest-restricted-' . uniqid());
        $this->restrictedUser->setPermissions(['objects', 'documents']);
        $this->restrictedUser->setWorkspacesObject([
            (new User\Workspace\DataObject())->setValues([
                'cId' => $this->objectFolder->getId(),
                'cPath' => $this->objectFolder->getFullpath(),
                'list' => true,
                'view' => true,
            ]),
        ]);
        $this->restrictedUser->setWorkspacesDocument([
            (new User\Workspace\Document())->setValues([
                'cId' => $this->documentFolder->getId(),
                'cPath' => $this->documentFolder->getFullpath(),
                'list' => true,
                'view' => true,
            ]),
        ]);
        $this->restrictedUser->save();
    }

    protected function tearDown(): void
    {
        $this->restrictedUser->delete();
        $this->objectFolder->delete();
        $this->documentFolder->delete();

        parent::tearDown();
    }

    public function testTokenForUserWithWorkspaceRestrictionsIsAccepted(): void
    {
        $token = $this->tokenFor($this->restrictedUser);

        $result = $this->safelyUnserialize(serialize($token));

        $this->assertInstanceOf(TokenInterface::class, $result);
        $this->assertInstanceOf(SecurityUser::class, $result->getUser());
        $this->assertSame($this->restrictedUser->getId(), $result->getUser()->getUser()->getId());
    }

    public function testCorruptedDataReturnsNullInsteadOfCrashing(): void
    {
        $result = $this->safelyUnserialize('this is not a valid serialized payload at all');

        $this->assertNull($result);
    }

    public function testPayloadWithUnknownClassReturnsNullInsteadOfCrashing(): void
    {
        $result = $this->safelyUnserialize('O:34:"Totally\Nonexistent\FakeClassXyz":0:{}');

        $this->assertNull($result);
    }

    private function tokenFor(User $user): PostAuthenticationToken
    {
        $securityUser = new SecurityUser($user);

        return new PostAuthenticationToken($securityUser, 'opendxp_admin', $securityUser->getRoles());
    }

    private function safelyUnserialize(string $data): mixed
    {
        $method = new ReflectionMethod(Authentication::class, 'safelyUnserialize');
        $method->setAccessible(true);

        return $method->invoke(null, $data);
    }
}
