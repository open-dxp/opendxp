<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Security\User;

use OpenDxp\Model\User;
use OpenDxp\Security\User\User as UserProxy;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Resolves the current opendxp user from the token storage.
 */
class TokenStorageUserResolver
{
    public function __construct(protected TokenStorageInterface $tokenStorage)
    {
    }

    public function getUser(): ?User
    {
        if ($proxy = $this->getUserProxy()) {
            return $proxy->getUser();
        }

        return null;
    }

    /**
     * Taken and adapted from framework base controller.
     *
     * The proxy is the wrapping OpenDxp\Security\User\User object implementing UserInterface.
     *
     */
    public function getUserProxy(): ?\OpenDxp\Security\User\User
    {
        if (!($token = $this->tokenStorage->getToken()) instanceof \Symfony\Component\Security\Core\Authentication\Token\TokenInterface) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return null;
        }

        if ($user instanceof UserProxy) {
            return $user;
        }

        return null;
    }
}
