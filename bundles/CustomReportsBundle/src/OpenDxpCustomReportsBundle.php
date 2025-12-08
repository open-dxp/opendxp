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

namespace OpenDxp\Bundle\CustomReportsBundle;

use OpenDxp\Bundle\CustomReportsBundle\DependencyInjection\OpenDxpCustomReportsExtension;
use OpenDxp\Extension\Bundle\AbstractOpenDxpBundle;
use OpenDxp\Extension\Bundle\OpenDxpBundleAdminClassicInterface;
use OpenDxp\Extension\Bundle\Traits\BundleAdminClassicTrait;
use OpenDxp\Extension\Bundle\Traits\PackageVersionTrait;
use Override;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class OpenDxpCustomReportsBundle extends AbstractOpenDxpBundle implements OpenDxpBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;
    use PackageVersionTrait;

    #[Override]
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new OpenDxpCustomReportsExtension();
        }

        return $this->extension;
    }

    public function getCssPaths(): array
    {
        return [];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/opendxpcustomreports/js/startup.js',
            '/bundles/opendxpcustomreports/js/opendxp/report/abstract.js',
            '/bundles/opendxpcustomreports/js/opendxp/report/broker.js',
            '/bundles/opendxpcustomreports/js/opendxp/report/panel.js',
            '/bundles/opendxpcustomreports/js/opendxp/layout/portlets/customreports.js',
            '/bundles/opendxpcustomreports/js/opendxp/report/custom/settings.js',
            '/bundles/opendxpcustomreports/js/opendxp/report/custom/definitions/sql.js',
            '/bundles/opendxpcustomreports/js/opendxp/report/custom/item.js',
            '/bundles/opendxpcustomreports/js/opendxp/report/custom/panel.js',
            '/bundles/opendxpcustomreports/js/opendxp/report/custom/report.js',
            '/bundles/opendxpcustomreports/js/opendxp/report/custom/toolbarenricher.js',
        ];
    }

    public function getInstaller(): ?Installer
    {
        return $this->container->get(Installer::class);
    }

    #[Override]
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
