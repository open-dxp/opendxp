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

namespace OpenDxp\Tests\Application\Service;

use OpenDxp\Event\Model\DataObject\ClassDefinition\UrlSlugAvailableSitesEvent;
use OpenDxp\Model\Site;
use RuntimeException;

final class AvailableSitesListener
{
    /**
     * @var array<string, list<int>>
     */
    private array $removedSiteIds = [];

    /**
     * @var list<string>
     */
    private array $unavailableLanguages = [];

    /**
     * @var list<string>
     */
    private array $failingLanguages = [];

    public function removeSite(Site $site, ?string $language): self
    {
        $this->removedSiteIds[$language ?? ''][] = $site->getId();

        return $this;
    }

    public function makeUnavailable(string $language): self
    {
        $this->unavailableLanguages[] = $language;

        return $this;
    }

    public function failFor(string $language): self
    {
        $this->failingLanguages[] = $language;

        return $this;
    }

    public function __invoke(UrlSlugAvailableSitesEvent $event): void
    {
        $language = $event->getLanguage() ?? '';

        if (in_array($language, $this->failingLanguages, true)) {
            throw new RuntimeException(sprintf('The listener fails for "%s".', $language));
        }

        $removedSiteIds = $this->removedSiteIds[$language] ?? [];

        $event->setSites(array_filter(
            $event->getSites(),
            static fn (Site $site): bool => !in_array($site->getId(), $removedSiteIds, true),
        ));
        $event->setLanguageAvailable(!in_array($language, $this->unavailableLanguages, true));
    }
}
