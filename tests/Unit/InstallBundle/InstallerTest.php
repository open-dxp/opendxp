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

namespace OpenDxp\Tests\Unit\InstallBundle;

use OpenDxp\Bundle\AdminBundle\OpenDxpAdminBundle;
use OpenDxp\Bundle\CoreBundle\Command\Bundle\InstallCommand as BundleInstallCommand;
use OpenDxp\Bundle\CoreBundle\OpenDxpCoreBundle;
use OpenDxp\Bundle\InstallBundle\Command\InstallCommand;
use OpenDxp\Bundle\InstallBundle\Installer;
use OpenDxp\Composer\PackageInfo;
use OpenDxp\Extension\Bundle\OpenDxpBundleLocator;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class OpenDxpUuidBundle
{
}

function installerFinding(array $bundles): Installer
{
    $locator = new class(new PackageInfo()) extends OpenDxpBundleLocator {
        public array $bundles = [];

        public function findBundles(): array
        {
            return $this->bundles;
        }
    };
    $locator->bundles = $bundles;

    return new Installer(new NullLogger(), new EventDispatcher(), $locator);
}

it('keys extension bundles by their short class name and leaves out core bundles', function () {
    $installer = installerFinding([OpenDxpAdminBundle::class, OpenDxpCoreBundle::class]);

    expect($installer->dispatchBundleSetupEvent()->getExtensionBundles())->toBe([
        'OpenDxpCoreBundle' => OpenDxpCoreBundle::class,
    ]);
});

it('keys an extension bundle by its class when its short name is taken', function () {
    $installer = installerFinding([InstallCommand::class, BundleInstallCommand::class, OpenDxpUuidBundle::class]);

    expect($installer->dispatchBundleSetupEvent()->getExtensionBundles())->toBe([
        'InstallCommand' => InstallCommand::class,
        BundleInstallCommand::class => BundleInstallCommand::class,
        OpenDxpUuidBundle::class => OpenDxpUuidBundle::class,
    ]);
});
