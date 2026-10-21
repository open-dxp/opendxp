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

namespace OpenDxp\Http\Request\Host;

use OpenDxp\Cache;
use OpenDxp\Model\Site;
use OpenDxp\SystemSettingsConfig;

final class TrustedHosts
{
    private const string SITE_PATTERNS_CACHE_KEY = 'opendxp_trusted_host_site_patterns';

    /**
     * @param list<string> $configuredHosts
     * @param iterable<TrustedHostPatternProviderInterface> $patternProviders
     */
    public function __construct(
        private readonly array $configuredHosts = [],
        private readonly iterable $patternProviders = [],
    ) {
    }

    /**
     * Returns the patterns of all trusted hosts in the form of `framework.trusted_hosts`.
     *
     * The patterns come from:
     * - the general domain
     * - `opendxp.security.trusted_hosts`
     * - the domains of every site
     * - the services tagged `opendxp.trusted_host_pattern_provider`
     *
     * @return list<string>
     */
    public function getPatterns(): array
    {
        $knownHostPatterns = [
            ...$this->getGeneralDomainPatterns(),
            ...array_map($this->toPattern(...), $this->configuredHosts),
            ...$this->getSitePatterns(),
        ];

        $patterns = $knownHostPatterns;
        foreach ($this->patternProviders as $provider) {
            $patterns = [...$patterns, ...$provider->getPatterns($knownHostPatterns)];
        }

        return array_values(array_unique($patterns));
    }

    public function isTrusted(string $host): bool
    {
        foreach ($this->getPatterns() as $pattern) {
            if (preg_match(sprintf('{%s}i', $pattern), $host) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function getGeneralDomainPatterns(): array
    {
        $domain = (string) (SystemSettingsConfig::get()['general']['domain'] ?? '');

        return $domain !== '' ? [$this->toPattern($domain)] : [];
    }

    /**
     * @return list<string>
     */
    private function getSitePatterns(): array
    {
        $patterns = Cache::load(self::SITE_PATTERNS_CACHE_KEY);
        if (is_array($patterns)) {
            return $patterns;
        }

        $patterns = [];
        foreach ((new Site\Listing())->load() as $site) {
            foreach ([$site->getMainDomain(), ...$site->getDomains()] as $domain) {
                if ($domain !== '') {
                    $patterns[] = $this->toPattern($domain);
                }
            }
        }

        Cache::save($patterns, self::SITE_PATTERNS_CACHE_KEY, ['system', 'site']);

        return $patterns;
    }

    /**
     * Converts a host or a wildcard host into an anchored pattern.
     *
     * The wildcard * stands for any characters:
     * - `*.example.com` trusts every subdomain of example.com, but not example.com itself
     * - `.*` at the end of a site domain is the older spelling of `*`
     */
    private function toPattern(string $host): string
    {
        $host = str_replace('.*', '*', strtolower($host));

        return sprintf('^%s$', str_replace('\*', '.+', preg_quote($host, '{')));
    }
}
