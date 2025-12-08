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

namespace OpenDxp\Bundle\XliffBundle;

use OpenDxp\Bundle\XliffBundle\DependencyInjection\Compiler\TranslationServicesPass;
use OpenDxp\Bundle\XliffBundle\DependencyInjection\OpenDxpXliffExtension;
use OpenDxp\Extension\Bundle\AbstractOpenDxpBundle;
use OpenDxp\Extension\Bundle\OpenDxpBundleAdminClassicInterface;
use OpenDxp\Extension\Bundle\Traits\BundleAdminClassicTrait;
use OpenDxp\Extension\Bundle\Traits\PackageVersionTrait;
use Override;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class OpenDxpXliffBundle extends AbstractOpenDxpBundle implements OpenDxpBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;
    use PackageVersionTrait;

    #[Override]
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new OpenDxpXliffExtension();
        }

        return $this->extension;
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/opendxpxliff/js/startup.js',
            '/bundles/opendxpxliff/js/settings.js',
        ];
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new TranslationServicesPass());
    }

    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }

    #[Override]
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
