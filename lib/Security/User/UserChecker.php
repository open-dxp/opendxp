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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Security\User;

use OpenDxp\Security\User\Exception\InvalidUserException;
use OpenDxp\Tool\Authentication;
use Override;
use Symfony\Component\Security\Core\User\InMemoryUserChecker;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * We're calling the valid user check in pre and post auth as it is cheap and
 * we're also dealing with pre authenticated tokens.
 */
class UserChecker extends InMemoryUserChecker
{
    #[Override]
    public function checkPreAuth(UserInterface $user): void
    {
        $this->checkValidUser($user);

        parent::checkPreAuth($user);
    }

    public function checkPostAuth(UserInterface $user): void
    {
        $this->checkValidUser($user);

        /** @var User $user */
        $opendxpUser = $user->getUser();

        // this is to reduce potential many last login update queries within a small time frame
        if ($opendxpUser->getLastLogin() <= time() - 60) {
            $opendxpUser->setLastLoginDate(); //set user current login date
        }

        parent::checkPostAuth($user);
    }

    private function checkValidUser(UserInterface $user): void
    {
        if (!($user instanceof User && Authentication::isValidUser($user->getUser()))) {
            $ex = new InvalidUserException('User is no valid OpenDxp admin user');
            $ex->setUser($user);

            throw $ex;
        }
    }
}
