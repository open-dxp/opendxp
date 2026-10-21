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

namespace OpenDxp\Bundle\SeoBundle\SiteDeclaration;

use OpenDxp\Model\Tool\SettingsStore;

final class SiteDeclarationRepository
{
    public function loadContent(SiteDeclarationInterface $declaration, int $siteId): string
    {
        $setting = SettingsStore::get($this->getId($declaration, $siteId), $this->getScope($declaration));

        return $setting !== null ? (string) $setting->getData() : '';
    }

    /**
     * @return array<int, string>
     */
    public function loadContentsBySiteId(SiteDeclarationInterface $declaration): array
    {
        $prefix = $this->getScope($declaration) . '-';
        $contents = [];

        foreach (SettingsStore::getIdsByScope($this->getScope($declaration)) as $id) {
            $siteId = (int) substr($id, strlen($prefix));
            $contents[$siteId] = $this->loadContent($declaration, $siteId);
        }

        return $contents;
    }

    public function saveContent(SiteDeclarationInterface $declaration, int $siteId, string $content): void
    {
        SettingsStore::set(
            $this->getId($declaration, $siteId),
            $content,
            SettingsStore::TYPE_STRING,
            $this->getScope($declaration),
        );
    }

    private function getScope(SiteDeclarationInterface $declaration): string
    {
        return 'site_declaration_' . $declaration->getName();
    }

    private function getId(SiteDeclarationInterface $declaration, int $siteId): string
    {
        return sprintf('%s-%d', $this->getScope($declaration), $siteId);
    }
}
