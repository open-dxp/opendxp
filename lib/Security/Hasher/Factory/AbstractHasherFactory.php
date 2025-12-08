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

namespace OpenDxp\Security\Hasher\Factory;

use ReflectionClass;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * @internal
 */
abstract class AbstractHasherFactory implements PasswordHasherFactoryInterface
{
    /**
     * Arguments passed to hasher constructor
     *
     * @var array
     */
    protected mixed $arguments = [];

    protected ?ReflectionClass $reflector = null;

    public function __construct(/**
     * Hasher class name to build
     */
    protected string $className, mixed $arguments = null)
    {
        if ($arguments) {
            if (!is_array($arguments)) {
                $arguments = [$arguments];
            }
        } else {
            $arguments = [];
        }

        $this->arguments = $arguments;
    }

    protected function buildPasswordHasher(ReflectionClass $reflectionClass): PasswordHasherInterface
    {
        /** @var PasswordHasherInterface $hasher */
        $hasher = $reflectionClass->newInstanceArgs($this->arguments);

        return $hasher;
    }

    protected function getReflector(): ReflectionClass
    {
        if (!$this->reflector instanceof \ReflectionClass) {
            $this->reflector = new ReflectionClass($this->className);
        }

        return $this->reflector;
    }
}
