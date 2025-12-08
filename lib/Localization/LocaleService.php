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

namespace OpenDxp\Localization;

use OpenDxp\Translation\Translator;
use ResourceBundle;
use Symfony\Component\HttpFoundation\RequestStack;

class LocaleService implements LocaleServiceInterface
{
    protected ?string $locale = null;

    public function __construct(protected ?RequestStack $requestStack = null, protected ?Translator $translator = null)
    {
    }

    public function isLocale(string $locale): bool
    {
        $locales = array_flip($this->getLocaleList());

        return isset($locales[$locale]);
    }

    public function findLocale(): string
    {
        if ($requestLocale = $this->getLocaleFromRequest()) {
            return $requestLocale;
        }

        $defaultLocale = \OpenDxp\Tool::getDefaultLanguage();
        if ($defaultLocale) {
            return $defaultLocale;
        }

        return '';
    }

    protected function getLocaleFromRequest(): ?string
    {
        if ($this->requestStack) {
            $mainRequest = $this->requestStack->getMainRequest();

            if ($mainRequest) {
                return $mainRequest->getLocale();
            }
        }

        return null;
    }

    public function getLocaleList(): array
    {
        return ResourceBundle::getLocales('');
    }

    public function getDisplayRegions(?string $locale = null): array
    {
        if (!$locale) {
            $locale = $this->findLocale();
        }

        $dataPath = OPENDXP_COMPOSER_PATH . '/umpirsky/country-list/data/';
        if (file_exists($dataPath . $locale . '/country.php')) {
            return include($dataPath . $locale . '/country.php');
        }

        return include($dataPath . 'en/country.php');
    }

    public function getLocale(): ?string
    {
        if (null === $this->locale) {
            $this->locale = $this->getLocaleFromRequest();
        }

        return $this->locale;
    }

    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;

        if ($locale) {
            if ($this->requestStack) {
                $mainRequest = $this->requestStack->getMainRequest();
                if ($mainRequest) {
                    $mainRequest->setLocale($locale);
                }

                $currentRequest = $this->requestStack->getCurrentRequest();
                if ($currentRequest) {
                    $currentRequest->setLocale($locale);
                }
            }

            if ($this->translator) {
                $this->translator->setLocale($locale);
            }
        }
    }

    public function hasLocale(): bool
    {
        return $this->getLocale() !== null;
    }
}
