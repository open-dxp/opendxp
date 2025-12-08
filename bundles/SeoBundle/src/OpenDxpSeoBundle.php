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

namespace OpenDxp\Bundle\SeoBundle;

use OpenDxp\Bundle\SeoBundle\DependencyInjection\OpenDxpSeoExtension;
use OpenDxp\Extension\Bundle\AbstractOpenDxpBundle;
use OpenDxp\Extension\Bundle\OpenDxpBundleAdminClassicInterface;
use OpenDxp\Extension\Bundle\Traits\BundleAdminClassicTrait;
use OpenDxp\Extension\Bundle\Traits\PackageVersionTrait;
use OpenDxp\HttpKernel\Bundle\DependentBundleInterface;
use OpenDxp\HttpKernel\BundleCollection\BundleCollection;
use Presta\SitemapBundle\PrestaSitemapBundle;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class OpenDxpSeoBundle extends AbstractOpenDxpBundle implements DependentBundleInterface, OpenDxpBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;
    use PackageVersionTrait;

    #[\Override]
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new OpenDxpSeoExtension();
        }

        return $this->extension;
    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/opendxpseo/css/icons.css',
        ];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/opendxpseo/js/startup.js',
            '/bundles/opendxpseo/js/httpErrorLog.js',
            '/bundles/opendxpseo/js/robotstxt.js',
            '/bundles/opendxpseo/js/seopanel.js',
            '/bundles/opendxpseo/js/redirects.js',
        ];
    }

    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }

    #[\Override]
    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public static function registerDependentBundles(BundleCollection $collection): void
    {
        $collection->addBundle(PrestaSitemapBundle::class);
    }
}
