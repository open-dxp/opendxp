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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle;

use OpenDxp\Bundle\GenericExecutionEngineBundle\DependencyInjection\OpenDxpGenericExecutionEngineExtension;
use OpenDxp\Extension\Bundle\AbstractOpenDxpBundle;
use OpenDxp\Extension\Bundle\Installer\InstallerInterface;
use OpenDxp\Extension\Bundle\OpenDxpBundleAdminClassicInterface;
use OpenDxp\Extension\Bundle\Traits\BundleAdminClassicTrait;
use OpenDxp\Extension\Bundle\Traits\PackageVersionTrait;
use Override;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class OpenDxpGenericExecutionEngineBundle extends AbstractOpenDxpBundle implements OpenDxpBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;
    use PackageVersionTrait;

    #[Override]
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new OpenDxpGenericExecutionEngineExtension();
        }

        return $this->extension;
    }

    #[Override]
    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public function getCssPaths(): array
    {
        if (!$this->getInstaller()->isInstalled()) {
            return [];
        }

        return [
            '/bundles/opendxpgenericexecutionengine/css/icons.css',
            '/bundles/opendxpgenericexecutionengine/css/job-run.css',
        ];
    }

    public function getJsPaths(): array
    {
        if (!$this->getInstaller()->isInstalled()) {
            return [];
        }

        return [
            '/bundles/opendxpgenericexecutionengine/js/startup.js',
            '/bundles/opendxpgenericexecutionengine/js/jobrun/panel.js',
            '/bundles/opendxpgenericexecutionengine/js/jobrun/menu.js',
            '/bundles/opendxpgenericexecutionengine/js/jobrun/runner.js',
        ];
    }

    public function getInstaller(): InstallerInterface
    {
        /** @var InstallerInterface|null */
        return $this->container->get(Installer::class);
    }
}
