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
use OpenDxp\Bundle\CoreBundle\OpenDxpCoreBundle;
use OpenDxp\Bundle\GlossaryBundle\OpenDxpGlossaryBundle;
use OpenDxp\Bundle\InstallBundle\Command\InstallCommand;
use OpenDxp\Bundle\InstallBundle\Event\BundleSetupEvent;
use OpenDxp\Bundle\InstallBundle\Event\InstallEvents;
use OpenDxp\Bundle\InstallBundle\Installer;
use OpenDxp\Bundle\InstallBundle\OpenDxpInstallBundle;
use OpenDxp\Bundle\SeoBundle\OpenDxpSeoBundle;
use OpenDxp\Bundle\XliffBundle\OpenDxpXliffBundle;
use OpenDxp\Composer\PackageInfo;
use OpenDxp\Extension\Bundle\OpenDxpBundleLocator;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;

beforeEach(function () {
    $locator = new class(new PackageInfo()) extends OpenDxpBundleLocator {
        public function findBundles(): array
        {
            return [OpenDxpAdminBundle::class, OpenDxpCoreBundle::class, OpenDxpInstallBundle::class];
        }
    };

    $this->eventDispatcher = new EventDispatcher();

    $this->installer = new class(new NullLogger(), $this->eventDispatcher, $locator) extends Installer {
        public ?array $selectedBundles = null;

        public array $registeredBundles = [];

        public function getRegisteredBundles(): array
        {
            return $this->registeredBundles;
        }

        public function setBundlesToInstall(array $bundlesToInstall = [], array $availableBundles = [], array $excludeFromBundlesPhp = []): void
        {
            $this->selectedBundles = array_values($bundlesToInstall);
        }
    };

    $this->tester = new CommandTester(new InstallCommand($this->installer, new EventDispatcher()));
});

afterEach(function () {
    putenv('OPENDXP_INSTALL_INSTALL_BUNDLES');
    putenv('OPENDXP_INSTALL_WITH_CORE_BUNDLES');
    putenv('OPENDXP_INSTALL_WITH_EXTENSION_BUNDLES');
});

it('installs all core bundles', function () {
    $this->tester->execute(['--with-core-bundles' => true], ['interactive' => false]);

    expect($this->installer->selectedBundles)->toBe(array_keys(Installer::INSTALLABLE_BUNDLES));
});

it('installs all extension bundles, but no core bundle among them', function () {
    $this->tester->execute(['--with-extension-bundles' => true], ['interactive' => false]);

    expect($this->installer->selectedBundles)->toBe(['OpenDxpCoreBundle', 'OpenDxpInstallBundle']);
});

it('combines the bundle options', function () {
    $this->tester->execute([
        '--with-extension-bundles' => true,
        '--install-bundles' => 'OpenDxpCoreBundle, OpenDxpUuidBundle',
    ], ['interactive' => false]);

    expect($this->installer->selectedBundles)->toBe(['OpenDxpCoreBundle', 'OpenDxpInstallBundle', 'OpenDxpUuidBundle']);
});

it('reads the bundle options from env vars', function () {
    putenv('OPENDXP_INSTALL_WITH_EXTENSION_BUNDLES=1');
    putenv('OPENDXP_INSTALL_INSTALL_BUNDLES=OpenDxpUuidBundle');

    $this->tester->execute([], ['interactive' => false]);

    expect($this->installer->selectedBundles)->toBe(['OpenDxpCoreBundle', 'OpenDxpInstallBundle', 'OpenDxpUuidBundle']);
});

it('ignores a bundle flag that an env var switches off', function () {
    putenv('OPENDXP_INSTALL_WITH_CORE_BUNDLES=0');

    $this->tester->execute([], ['interactive' => false]);

    expect($this->installer->selectedBundles)->toBeNull();
});

it('warns about unknown bundles', function () {
    $this->tester->execute(['--install-bundles' => 'OpenDxpUuidBundle,UnknownBundle'], ['interactive' => false]);

    expect($this->installer->selectedBundles)->toBe(['OpenDxpUuidBundle', 'UnknownBundle'])
        ->and($this->tester->getDisplay())->toContain('Ignoring unknown bundles: UnknownBundle');
});

it('selects no bundles without a bundle option', function () {
    $this->tester->execute([], ['interactive' => false]);

    expect($this->installer->selectedBundles)->toBeNull();
});

function executeInteractively(CommandTester $tester, array $inputs, array $options = []): void
{
    $tester->setInputs($inputs);
    $tester->execute([
        '--admin-username' => 'admin',
        '--admin-password' => 'admin',
        '--mysql-username' => 'db',
        '--mysql-password' => 'db',
        '--mysql-database' => 'db',
        ...$options,
    ]);
}

it('asks only for the extension bundles with all core bundles', function () {
    executeInteractively($this->tester, ['yes', '1', 'no'], ['--with-core-bundles' => true]);

    expect($this->installer->selectedBundles)->toBe([...array_keys(Installer::INSTALLABLE_BUNDLES), 'OpenDxpInstallBundle'])
        ->and($this->tester->getDisplay())->not->toContain('install core bundles');
});

it('asks only for the core bundles with all extension bundles', function () {
    executeInteractively($this->tester, ['yes', '5', 'no'], ['--with-extension-bundles' => true]);

    expect($this->installer->selectedBundles)->toBe(['OpenDxpCoreBundle', 'OpenDxpInstallBundle', 'OpenDxpSimpleBackendSearchBundle'])
        ->and($this->tester->getDisplay())->not->toContain('install extension bundles');
});

it('asks for no bundles with both bundle flags', function () {
    executeInteractively($this->tester, ['no'], ['--with-core-bundles' => true, '--with-extension-bundles' => true]);

    expect($this->installer->selectedBundles)->toBe([...array_keys(Installer::INSTALLABLE_BUNDLES), 'OpenDxpCoreBundle', 'OpenDxpInstallBundle'])
        ->and($this->tester->getDisplay())->not->toContain('Do you want to install');
});

it('asks for no bundles with a bundle list', function () {
    executeInteractively($this->tester, ['no'], ['--install-bundles' => 'OpenDxpUuidBundle']);

    expect($this->installer->selectedBundles)->toBe(['OpenDxpUuidBundle'])
        ->and($this->tester->getDisplay())->not->toContain('Do you want to install');
});

it('asks for the core and the extension bundles one after the other', function () {
    executeInteractively($this->tester, ['yes', '5', 'yes', '1', 'no']);

    expect($this->installer->selectedBundles)->toBe(['OpenDxpSimpleBackendSearchBundle', 'OpenDxpInstallBundle']);
});

it('preselects the core bundles that bundles.php registers and installs them on enter', function () {
    $this->installer->registeredBundles = [OpenDxpGlossaryBundle::class, OpenDxpSeoBundle::class];

    executeInteractively($this->tester, ['', '', 'no', 'no']);

    expect($this->installer->selectedBundles)->toBe(['OpenDxpGlossaryBundle', 'OpenDxpSeoBundle']);
});

it('preselects the bundles a subscriber recommends', function () {
    $this->installer->registeredBundles = [OpenDxpSeoBundle::class];
    $this->eventDispatcher->addListener(
        InstallEvents::EVENT_BUNDLE_SETUP,
        fn (BundleSetupEvent $event) => $event->addInstallableBundle('OpenDxpXliffBundle', OpenDxpXliffBundle::class, true)
    );

    executeInteractively($this->tester, ['yes', '', 'no', 'no']);

    expect($this->installer->selectedBundles)->toBe(['OpenDxpSeoBundle', 'OpenDxpXliffBundle']);
});

it('preselects the extension bundles that bundles.php registers', function () {
    $this->installer->registeredBundles = [OpenDxpInstallBundle::class];

    executeInteractively($this->tester, ['no', 'yes', '', 'no']);

    expect($this->installer->selectedBundles)->toBe(['OpenDxpInstallBundle']);
});

it('selects no bundles when every bundle question is declined', function () {
    executeInteractively($this->tester, ['no', 'no', 'no']);

    expect($this->installer->selectedBundles)->toBeNull();
});
