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

namespace OpenDxp\Workflow;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\EventListener\ExpressionLanguage;
use Symfony\Component\Workflow\WorkflowInterface;

class ExpressionService
{
    public function __construct(private readonly ExpressionLanguage $expressionLanguage, private readonly TokenStorageInterface $tokenStorage, private readonly AuthorizationCheckerInterface $authenticationChecker, private readonly AuthenticationTrustResolverInterface $trustResolver, private readonly ?RoleHierarchyInterface $roleHierarchy = null, private readonly ?ValidatorInterface $validator = null)
    {
    }

    public function evaluateExpression(WorkflowInterface $workflow, object $subject, string $expression): mixed
    {
        return $this->expressionLanguage->evaluate($expression, $this->getVariables($subject));
    }

    // code should be sync with Symfony\Component\Security\Core\Authorization\Voter\ExpressionVoter
    private function getVariables(object $subject): array
    {
        $token = $this->tokenStorage->getToken() ?: new NullToken;

        $roleNames = $token->getRoleNames();
        if ($this->roleHierarchy instanceof \Symfony\Component\Security\Core\Role\RoleHierarchyInterface) {
            $roleNames = $this->roleHierarchy->getReachableRoleNames($roleNames);
        }

        $variables = [
            'token' => $token,
            'user' => $token->getUser() ?: 'anonymous',
            'object' => $subject,
            'subject' => $subject,
            'role_names' => $roleNames,
            // needed for the is_* expression function
            'trust_resolver' => $this->trustResolver,
            // needed for the is_granted expression function
            'auth_checker' => $this->authenticationChecker,
            // needed for the is_valid expression function
            'validator' => $this->validator,
        ];

        // this is mainly to propose a better experience when the expression is used
        // in an access control rule, as the developer does not know that it's going
        // to be handled by this voter
        if ($subject instanceof Request) {
            $variables['request'] = $subject;
        }

        return $variables;
    }
}
