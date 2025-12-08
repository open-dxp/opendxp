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

namespace OpenDxp\HttpKernel\BundleLocator;

use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class BundleLocator implements BundleLocatorInterface
{
    private array $bundleCache = [];

    public function __construct(private readonly KernelInterface $kernel)
    {
    }

    public function getBundle(object|string $class): BundleInterface
    {
        return $this->getBundleForClass($class);
    }

    public function getBundlePath(object|string $class): string
    {
        return $this->getBundleForClass($class)->getPath();
    }

    /**
     * @throws ReflectionException
     */
    private function getBundleForClass(object|string $class): BundleInterface
    {
        if (is_object($class)) {
            $class = $class::class;
        }

        if (!isset($this->bundleCache[$class])) {
            $this->bundleCache[$class] = $this->findBundleForClass($class);
        }

        return $this->bundleCache[$class];
    }

    /**
     * @throws ReflectionException
     */
    private function findBundleForClass(string $class): BundleInterface
    {
        // see TemplateGuesser from SensioFrameworkExtraBundle
        $reflectionClass = new ReflectionClass($class);
        $bundles = $this->kernel->getBundles();

        do {
            $namespace = $reflectionClass->getNamespaceName();

            foreach ($bundles as $bundle) {
                if (str_starts_with($namespace, $bundle->getNamespace() . '\\')) {
                    return $bundle;
                }
            }

            $reflectionClass = $reflectionClass->getParentClass();
        } while ($reflectionClass);

        throw new NotFoundException(sprintf('Unable to find bundle for class %s', $class));
    }
}
