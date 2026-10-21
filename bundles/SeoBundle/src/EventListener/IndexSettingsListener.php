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

namespace OpenDxp\Bundle\SeoBundle\EventListener;

use OpenDxp\Bundle\AdminBundle\Event\IndexActionSettingsEvent;
use OpenDxp\Bundle\SeoBundle\SiteDeclaration\SiteDeclarationRegistry;

/**
 * @internal
 */
final class IndexSettingsListener
{
    public function __construct(private readonly SiteDeclarationRegistry $registry)
    {
    }

    public function indexSettings(IndexActionSettingsEvent $settingsEvent): void
    {
        $declarations = [];

        foreach ($this->registry->getDeclarations() as $declaration) {
            $declarations[] = [
                'name' => $declaration->getName(),
                'path' => $declaration->getPath(),
            ];
        }

        $settingsEvent->addSetting('site_declarations', $declarations);
    }
}
