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

use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\ClassDefinition\Data\Localizedfields;
use OpenDxp\Model\DataObject\ClassDefinition\Data\Password;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Fieldcollection;
use OpenDxp\Model\DataObject\Objectbrick;
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
 *
 * A template never reads the file of an asset or the hash of a password. It reads a field of an object by its getter.
 */
final class SecurityPolicy implements SecurityPolicyInterface
{
    private const array FILE_METHODS = ['getdata', 'getstream', 'gettext', 'getlocalfile', 'gettemporaryfile'];

    private const array FIELD_BY_NAME_METHODS = ['get', 'getvalueforfieldname', 'getvaluefromparent'];

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
        return in_array($function, $this->allowedFunctions, true);
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

        if ($method === '__toString') {
            return true;
        }

        return preg_match('/^(get|is|has)(?![a-z])/', $method) === 1
            && !$this->isSecretAccessor($obj, strtolower($method));
    }

    private function isSecretAccessor(object $obj, string $method): bool
    {
        if ($obj instanceof Asset) {
            return in_array($method, self::FILE_METHODS, true);
        }

        $fieldDefinitions = $this->getFieldDefinitions($obj);

        if ($fieldDefinitions === []) {
            return false;
        }

        if (in_array($method, self::FIELD_BY_NAME_METHODS, true)) {
            return true;
        }

        foreach ($fieldDefinitions as $fieldDefinition) {
            if ($fieldDefinition instanceof Password && $method === 'get' . strtolower($fieldDefinition->getName())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the fields of the object together with the fields in its localized fields.
     *
     * @return array<string, Data>
     */
    private function getFieldDefinitions(object $obj): array
    {
        $definition = match (true) {
            $obj instanceof Concrete => $obj->getClass(),
            $obj instanceof Fieldcollection\Data\AbstractData,
            $obj instanceof Objectbrick\Data\AbstractData => $obj->getDefinition(),
            default => null,
        };

        if ($definition === null) {
            return [];
        }

        $fieldDefinitions = $definition->getFieldDefinitions();
        $localizedFields = $fieldDefinitions['localizedfields'] ?? null;

        if ($localizedFields instanceof Localizedfields) {
            return [...$fieldDefinitions, ...$localizedFields->getFieldDefinitions()];
        }

        return $fieldDefinitions;
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
