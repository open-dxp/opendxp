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

namespace OpenDxp\Bundle\TinymceBundle;

use OpenDxp\Bundle\TinymceBundle\DependencyInjection\OpenDxpTinymceExtension;
use OpenDxp\Extension\Bundle\AbstractOpenDxpBundle;
use OpenDxp\Extension\Bundle\OpenDxpBundleAdminClassicInterface;
use OpenDxp\Extension\Bundle\Traits\BundleAdminClassicTrait;
use OpenDxp\Extension\Bundle\Traits\PackageVersionTrait;
use OpenDxp\Helper\EncoreHelper;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class OpenDxpTinymceBundle extends AbstractOpenDxpBundle implements OpenDxpBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;
    use PackageVersionTrait;

    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new OpenDxpTinymceExtension();
        }

        return $this->extension;
    }

    public function getCssPaths(): array
    {
        return [
           '/bundles/opendxptinymce/css/editor.css',
        ];
    }

    public function getEditmodeCssPaths(): array
    {
        return [
            '/bundles/opendxptinymce/css/editor.css',
        ];
    }

    public function getJsPaths(): array
    {
        return $this->getAllJsPaths();
    }

    public function getEditmodeJsPaths(): array
    {
        return $this->getAllJsPaths();
    }

    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    private function getAllJsPaths(): array
    {
        $paths = EncoreHelper::getBuildPathsFromEntrypoints($this->getPath() . '/public/build/tinymce/entrypoints.json');
        $paths[]= '/bundles/opendxptinymce/js/editor.js';

        return $paths;
    }
}
