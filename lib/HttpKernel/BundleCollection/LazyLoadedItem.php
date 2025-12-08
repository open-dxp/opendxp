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

namespace OpenDxp\HttpKernel\BundleCollection;

use InvalidArgumentException;
use OpenDxp\Extension\Bundle\OpenDxpBundleInterface;
use OpenDxp\HttpKernel\Bundle\DependentBundleInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class LazyLoadedItem extends AbstractItem
{
    private readonly string $className;

    private ?BundleInterface $bundle = null;

    private static array $classImplementsCache = [];

    /**
     * LazyLoadedItem constructor.
     *
     */
    public function __construct(
        string $className,
        int $priority = 0,
        array $environments = [],
        string $source = self::SOURCE_PROGRAMATICALLY
    ) {
        if (!class_exists($className)) {
            throw new InvalidArgumentException(sprintf('The class "%s" does not exist', $className));
        }

        $this->className = $className;

        parent::__construct($priority, $environments, $source);
    }

    public function getBundleIdentifier(): string
    {
        return $this->className;
    }

    public function getBundle(): BundleInterface
    {
        if (!$this->bundle instanceof \Symfony\Component\HttpKernel\Bundle\BundleInterface) {
            $className = $this->className;

            $this->bundle = new $className;
        }

        return $this->bundle;
    }

    public function isOpenDxpBundle(): bool
    {
        if ($this->bundle instanceof \Symfony\Component\HttpKernel\Bundle\BundleInterface) {
            return $this->bundle instanceof OpenDxpBundleInterface;
        }

        // do not initialize bundle - check class instead
        return $this->implementsInterface($this->className, OpenDxpBundleInterface::class);
    }

    public function registerDependencies(BundleCollection $collection): void
    {
        if ($this->implementsInterface($this->className, DependentBundleInterface::class)) {
            /** @var class-string<DependentBundleInterface> $className */
            $className = $this->className;
            $className::registerDependentBundles($collection);
        }
    }

    private function implementsInterface(string $className, string $interfaceName): bool
    {
        if (!isset(self::$classImplementsCache[$className])) {
            self::$classImplementsCache[$className] = class_implements($className);
        }

        return in_array($interfaceName, self::$classImplementsCache[$className]);
    }
}
