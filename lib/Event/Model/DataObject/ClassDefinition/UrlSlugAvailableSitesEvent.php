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

namespace OpenDxp\Event\Model\DataObject\ClassDefinition;

use OpenDxp\Model\DataObject\ClassDefinition\Data\UrlSlug;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Site;
use Symfony\Contracts\EventDispatcher\Event;

final class UrlSlugAvailableSitesEvent extends Event
{
    private bool $languageAvailable = true;

    /**
     * @param Site[] $sites
     */
    public function __construct(
        private readonly Concrete $object,
        private readonly UrlSlug $urlSlug,
        private readonly ?string $language,
        private array $sites,
    ) {
    }

    public function getObject(): Concrete
    {
        return $this->object;
    }

    public function getUrlSlug(): UrlSlug
    {
        return $this->urlSlug;
    }

    /**
     * Returns the language of the slug, or null outside localized fields.
     */
    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * @return Site[]
     */
    public function getSites(): array
    {
        return $this->sites;
    }

    /**
     * @param Site[] $sites
     */
    public function setSites(array $sites): void
    {
        $this->sites = array_values($sites);
    }

    public function isLanguageAvailable(): bool
    {
        return $this->languageAvailable;
    }

    /**
     * Takes the language out of the field when it is false. The field then has no slug in this language, not even
     * the fallback slug.
     */
    public function setLanguageAvailable(bool $languageAvailable): void
    {
        $this->languageAvailable = $languageAvailable;
    }
}
