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

namespace OpenDxp\Model\Translation;

use OpenDxp\Model;
use OpenDxp\Model\Exception\NotFoundException;

/**
 * @method \OpenDxp\Model\Translation\Listing\Dao getDao()
 * @method Model\Translation[] load()
 * @method list<array<string,mixed>> loadRaw()
 * @method Model\Translation|false current()
 * @method int getTotalCount()
 * @method void onCreateQueryBuilder(?callable $callback)
 * @method void cleanup()
 *
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @internal
     *
     * @var int maximum number of cacheable items
     */
    protected static int $cacheLimit = 5000;

    /**
     * @internal
     *
     */
    protected string $domain = Model\Translation::DOMAIN_DEFAULT;

    /**
     * @internal
     *
     * @var string[]|null
     */
    protected ?array $languages = null;

    #[\Override]
    public function isValidOrderKey(string $key): bool
    {
        return in_array($key, ['key', 'type']) || in_array($key, $this->getLanguages());
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): void
    {
        if (!Model\Translation::isAValidDomain($domain)) {
            throw new NotFoundException(
                sprintf(
                    'Either translation domain %s is not registered in config `opendxp.translations.domains` or table "%s" does not exist',
                    'translations_' . $domain,
                    $domain
                )
            );
        }

        $this->domain = $domain;
    }

    /**
     * @return string[]|null
     */
    public function getLanguages(): ?array
    {
        return $this->languages;
    }

    /**
     * @param string[]|null $languages
     */
    public function setLanguages(?array $languages): void
    {
        $this->languages = $languages;
    }

    /**
     * @return \OpenDxp\Model\Translation[]
     */
    public function getTranslations(): array
    {
        return $this->getData();
    }

    public function setTranslations(array $translations): Listing
    {
        return $this->setData($translations);
    }

    public static function getCacheLimit(): int
    {
        return self::$cacheLimit;
    }

    public static function setCacheLimit(int $cacheLimit): void
    {
        self::$cacheLimit = $cacheLimit;
    }
}
