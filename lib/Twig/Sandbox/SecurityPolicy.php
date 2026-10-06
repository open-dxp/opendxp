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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Twig\Sandbox;

use Twig\Markup;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityPolicyInterface;
use Twig\Template;

/**
 * A template may read the objects of the readable classes through their getters, `is` and `has` methods,
 * `__toString()` and properties. Any other method has to be allowed for its class.
 */
final class SecurityPolicy implements SecurityPolicyInterface
{
    /**
     * @param string[] $allowedTags
     * @param string[] $allowedFilters
     * @param string[] $allowedFunctions
     * @param array<class-string, string[]> $allowedMethods
     * @param class-string[] $readableClasses
     */
    public function __construct(
        private array $allowedTags = [],
        private array $allowedFilters = [],
        private array $allowedFunctions = [],
        private array $allowedMethods = [],
        private array $readableClasses = [],
    ) {
    }

    public function setAllowedTags(array $tags): void
    {
        $this->allowedTags = $tags;
    }

    public function setAllowedFilters(array $filters): void
    {
        $this->allowedFilters = $filters;
    }

    public function setAllowedFunctions(array $functions): void
    {
        $this->allowedFunctions = $functions;
    }

    /**
     * The policy has no list of tests and allows every test.
     *
     * @param string[] $tags
     * @param string[] $filters
     * @param string[] $functions
     * @param string[] $tests
     */
    public function checkSecurity($tags, $filters, $functions, array $tests = []): void
    {
        foreach ($tags as $tag) {
            if (!in_array($tag, $this->allowedTags)) {
                throw new SecurityNotAllowedTagError(sprintf('Tag "%s" is not allowed.', $tag), $tag);
            }
        }

        foreach ($filters as $filter) {
            if (!in_array($filter, $this->allowedFilters)) {
                throw new SecurityNotAllowedFilterError(sprintf('Filter "%s" is not allowed.', $filter), $filter);
            }
        }

        foreach ($functions as $function) {
            if (!$this->isAllowedFunction($function)) {
                throw new SecurityNotAllowedFunctionError(sprintf('Function "%s" is not allowed.', $function), $function);
            }
        }
    }

    /**
     * @param object $obj
     * @param string $method
     */
    public function checkMethodAllowed($obj, $method): void
    {
        if ($obj instanceof Template || $obj instanceof Markup) {
            return;
        }

        if ($this->isReadable($obj) && $this->isAccessor($obj, $method)) {
            return;
        }

        if ($this->isAllowedMethod($obj, $method)) {
            return;
        }

        throw new SecurityNotAllowedMethodError(
            sprintf('Calling "%s" method on a "%s" object is not allowed.', $method, $obj::class),
            $obj::class,
            $method,
        );
    }

    /**
     * @param object $obj
     * @param string $property
     */
    public function checkPropertyAllowed($obj, $property): void
    {
        if ($this->isReadable($obj)) {
            return;
        }

        throw new SecurityNotAllowedPropertyError(
            sprintf('Calling "%s" property on a "%s" object is not allowed.', $property, $obj::class),
            $obj::class,
            $property,
        );
    }

    private function isAllowedFunction(string $function): bool
    {
        if (in_array($function, $this->allowedFunctions, true)) {
            return true;
        }

        // The dump prints the inner state of an object, which the method checks never see.
        return str_starts_with($function, 'opendxp_')
            && $function !== 'opendxp_dump';
    }

    private function isReadable(object $obj): bool
    {
        foreach ($this->readableClasses as $class) {
            if ($obj instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * A method that only exists through `__call()` is no accessor. A model hands such a call on to its dao.
     */
    private function isAccessor(object $obj, string $method): bool
    {
        if (!method_exists($obj, $method)) {
            return false;
        }

        return $method === '__toString'
            || preg_match('/^(get|is|has)(?![a-z])/', $method) === 1;
    }

    private function isAllowedMethod(object $obj, string $method): bool
    {
        foreach ($this->allowedMethods as $class => $methods) {
            if (!$obj instanceof $class) {
                continue;
            }

            foreach ($methods as $allowed) {
                if (strcasecmp($allowed, $method) === 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
