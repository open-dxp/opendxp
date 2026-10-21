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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp;

use Exception;
use GuzzleHttp\RequestOptions;
use Locale;
use OpenDxp;
use OpenDxp\Http\Request\Host\GeneralHostProviderInterface;
use OpenDxp\Http\Request\Host\GeneralHostResolver;
use OpenDxp\Http\RequestHelper;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model\Element;
use Symfony\Component\HttpFoundation\Request;

final class Tool
{
    /**
     * Sets the current request to use when resolving request at early
     * stages (before container is loaded)
     */
    private static ?Request $currentRequest = null;

    protected static array $notFoundClassNames = [];

    protected static array $validLanguages = [];

    protected static array $requiredLanguages = [];

    /**
     * Sets the current request to operate on
     *
     * @internal
     *
     * @deprecated since OpenDXP 1.3 and will be removed in 2.0
     */
    public static function setCurrentRequest(?Request $request = null): void
    {
        trigger_deprecation('open-dxp/opendxp', '1.3', 'Calling "%s()" is deprecated and will be removed in 2.0. The request is managed via the RequestStack.', __METHOD__);

        self::$currentRequest = $request;
    }

    /**
     * @internal
     *
     * @deprecated since OpenDXP 1.3 and will be removed in 2.0
     */
    public static function hasCurrentRequest(): bool
    {
        trigger_deprecation('open-dxp/opendxp', '1.3', 'Calling "%s()" is deprecated and will be removed in 2.0. The request is managed via the RequestStack.', __METHOD__);

        return self::$currentRequest instanceof Request;
    }

    /**
     * Checks, if the given language is configured in opendxp system
     * settings at "Localization & Internationalization (i18n/l10n)".
     * Returns true, if the language is valid or no language is
     * configured at all, false otherwise.
     */
    public static function isValidLanguage(?string $language): bool
    {
        $language = (string) $language; // cast to string
        $languages = self::getValidLanguages();

        // if not configured, every language is valid
        if (!$languages) {
            return true;
        }

        return in_array($language, $languages);
    }

    /**
     * Returns an array of language codes that configured for this system
     * in opendxp system settings at "Localization & Internationalization (i18n/l10n)".
     * An empty array is returned if no languages are configured.
     *
     * @return string[]
     */
    public static function getValidLanguages(): array
    {
        if (self::$validLanguages === []) {
            $config = SystemSettingsConfig::get()['general'];
            if (empty($config['valid_languages'])) {
                return [];
            }

            $validLanguages = $config['valid_languages'];

            if (!is_array($validLanguages)) {
                $validLanguages = [];
            }

            self::$validLanguages = $validLanguages;
        }

        return self::$validLanguages;
    }

    public static function getRequiredLanguages(): array
    {
        if (self::$requiredLanguages === []) {
            $config = SystemSettingsConfig::get()['general'];
            if (empty($config['required_languages'])) {
                return Tool::getValidLanguages();
            }

            $requiredLanguages = $config['required_languages'];

            if (!is_array($requiredLanguages)) {
                $requiredLanguages = Tool::getValidLanguages();
            }

            self::$requiredLanguages = $requiredLanguages;
        }

        return self::$requiredLanguages;
    }

    /**
     * @return string[]
     *
     * @internal
     */
    public static function getFallbackLanguagesFor(string $language): array
    {
        $languages = [];

        $config = SystemSettingsConfig::get()['general'];
        if (!empty($config['fallback_languages'][$language])) {
            $fallbackLanguages = explode(',', $config['fallback_languages'][$language]);
            foreach ($fallbackLanguages as $l) {
                if (self::isValidLanguage($l)) {
                    $languages[] = trim($l);
                }
            }
        }

        return $languages;
    }

    /**
     * Returns the default language for this system. If no default is set,
     * returns the first language, or null, if no languages are configured
     * at all.
     */
    public static function getDefaultLanguage(): ?string
    {
        $config = SystemSettingsConfig::get()['general'];
        $defaultLanguage = $config['default_language'] ?? null;
        $languages = self::getValidLanguages();
        if ($languages !== [] && in_array($defaultLanguage, $languages)) {
            return $defaultLanguage;
        }

        if ($languages !== []) {
            return $languages[0];
        }

        return null;
    }

    /**
     * @return array<string, string>
     *
     * @throws Exception
     */
    public static function getSupportedLocales(): array
    {
        $localeService = OpenDxp::getContainer()->get(LocaleServiceInterface::class);
        $locale = $localeService->findLocale();

        $cacheKey = 'system_supported_locales_' . strtolower((string) $locale);
        if (!$languageOptions = Cache::load($cacheKey)) {
            $languages = $localeService->getLocaleList();

            $languageOptions = [];
            foreach ($languages as $code) {
                $translation = Locale::getDisplayLanguage($code, $locale);
                $displayRegion = Locale::getDisplayRegion($code, $locale);

                if ($displayRegion) {
                    $translation .= ' (' . $displayRegion . ')';
                }

                if (!$translation) {
                    $translation = $code;
                }

                $languageOptions[$code] = $translation;
            }

            asort($languageOptions);

            Cache::save($languageOptions, $cacheKey, ['system']);
        }

        return $languageOptions;
    }

    /**
     * Trying to get BCP 47 format
     *
     * @return array<string, string>
     *
     * @throws Exception
     */
    public static function getSupportedJSLocales(): array
    {
        $localeService = OpenDxp::getContainer()->get(LocaleServiceInterface::class);
        $locale = $localeService->findLocale();

        $cacheKey = 'system_supported_js_locales_' . strtolower((string)$locale);
        if (!$languageOptions = Cache::load($cacheKey)) {
            $languages = $localeService->getLocaleList();

            $languageOptions = [];
            foreach ($languages as $code) {
                if (substr_count($code, '_') > 1) {
                    continue;
                }
                $codeBCP = str_replace('_', '-', $code);

                $displayName = Locale::getDisplayName($code, $locale);
                $displayRegion = Locale::getDisplayRegion($code, $locale);

                $translation = $displayRegion ? $displayRegion . ' [' . $codeBCP . ']' : $displayName . ' [' . $codeBCP . ']';

                $languageOptions[$codeBCP] = $translation;
            }

            asort($languageOptions);

            Cache::save($languageOptions, $cacheKey, ['system']);
        }

        return $languageOptions;
    }

    private static function resolveRequest(?Request $request = null): ?Request
    {
        if (!$request instanceof Request) {
            // do an extra check for the container as we might be in a state where no container is set yet
            if (OpenDxp::hasContainer()) {
                $request = OpenDxp::getContainer()->get('request_stack')->getMainRequest();
            } elseif (self::$currentRequest instanceof Request) {
                return self::$currentRequest;
            }
        }

        return $request;
    }

    public static function isFrontend(?Request $request = null): bool
    {
        if (!$request instanceof Request) {
            $request = OpenDxp::getContainer()->get('request_stack')->getMainRequest();
        }

        if (null === $request) {
            return false;
        }

        return OpenDxp::getContainer()
            ->get(RequestHelper::class)
            ->isFrontendRequest($request);
    }

    /**
     * eg. editmode, preview, version preview, always when it is a "frontend-request", but called out of the admin
     */
    public static function isFrontendRequestByAdmin(?Request $request = null): bool
    {
        $request = self::resolveRequest($request);

        if (!$request instanceof Request) {
            return false;
        }

        return OpenDxp::getContainer()
            ->get(RequestHelper::class)
            ->isFrontendRequestByAdmin($request);
    }

    /**
     * Verify element request (eg. editmode, preview, version preview) called within admin, with permissions.
     */
    public static function isElementRequestByAdmin(Request $request, Element\ElementInterface $element): bool
    {
        if (!self::isFrontendRequestByAdmin($request)) {
            return false;
        }

        $user = Tool\Authentication::authenticateSession($request);

        return $user && $element->isAllowed('view', $user);
    }

    /**
     * @internal
     */
    public static function useFrontendOutputFilters(?Request $request = null): bool
    {
        $request = self::resolveRequest($request);

        if (!$request instanceof Request) {
            return false;
        }

        if (!self::isFrontend($request)) {
            return false;
        }

        if (self::isFrontendRequestByAdmin($request)) {
            return false;
        }

        $requestKeys = [...array_keys($request->query->all()), ...array_keys($request->request->all())];

        // check for manually disabled ?opendxp_outputfilters_disabled=true
        return !(in_array('opendxp_outputfilters_disabled', $requestKeys) && OpenDxp::inDebugMode());
    }

    /**
     * @internal
     */
    public static function getHostname(?Request $request = null): ?string
    {
        $request = self::resolveRequest($request);

        if (!$request instanceof Request || !$request->getHost()) {
            /** @var GeneralHostResolver $generalHostResolver */
            $generalHostResolver = OpenDxp::getContainer()->get(GeneralHostResolver::class);

            return $generalHostResolver->resolve([GeneralHostProviderInterface::CONTEXT_SOURCE => $request]);
        }

        return $request->getHost();
    }

    /**
     * @internal
     */
    public static function getRequestScheme(?Request $request = null): string
    {
        $request = self::resolveRequest($request);

        if (!$request instanceof Request) {
            return OpenDxp::getContainer()->get(RequestHelper::class)->getScheme();
        }

        return $request->getScheme();
    }

    /**
     * Returns the host URL
     *
     * @param string|null $useProtocol use a specific protocol
     */
    public static function getHostUrl(?string $useProtocol = null, ?Request $request = null): string
    {
        $request = self::resolveRequest($request);

        $hostname = '';
        $port = '';

        if ($request instanceof Request) {
            $protocol = $request->getScheme();
            $hostname = $request->getHost();

            if (!in_array($request->getPort(), [443, 80])) {
                $port = ':' . $request->getPort();
            }
        } else {
            $protocol = OpenDxp::getContainer()->get(RequestHelper::class)->getScheme();
        }

        if (!$hostname || $hostname === 'localhost') {
            /** @var GeneralHostResolver $generalHostResolver */
            $generalHostResolver = OpenDxp::getContainer()->get(GeneralHostResolver::class);
            $hostname = $generalHostResolver->resolve([GeneralHostProviderInterface::CONTEXT_SOURCE => $request]);

            if (!$hostname) {
                Logger::warn('Couldn\'t determine HTTP Host. No Domain set in "Settings" -> "System" -> "Website" -> "Domain"');

                return '';
            }
        }

        if ($useProtocol) {
            $protocol = $useProtocol;
        }

        return $protocol . '://' . $hostname . $port;
    }

    /**
     * @internal
     */
    public static function getClientIp(?Request $request = null): ?string
    {
        $request = self::resolveRequest($request);
        if ($request) {
            return $request->getClientIp();
        }

        // fallback to $_SERVER variables
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            return null;
        }

        $ips = explode(',', $ip);

        return trim(array_shift($ips));
    }

    /**
     * @internal
     */
    public static function getAnonymizedClientIp(?Request $request = null): ?string
    {
        $request = self::resolveRequest($request);

        if (!$request instanceof Request) {
            return null;
        }

        return OpenDxp::getContainer()
            ->get(RequestHelper::class)
            ->getAnonymizedClientIp($request);
    }

    /**
     * @throws Exception
     */
    public static function getMail(array|string|null $recipients = null, ?string $subject = null): Mail
    {
        $mail = new Mail();

        if ($recipients) {
            if (is_string($recipients)) {
                $mail->addTo($recipients);
            } elseif (is_array($recipients)) {
                foreach ($recipients as $recipient) {
                    $mail->addTo($recipient);
                }
            }
        }

        if ($subject) {
            $mail->subject($subject);
        }

        return $mail;
    }

    public static function getHttpData(string $url, array $paramsGet = [], array $paramsPost = [], array $options = []): false|string
    {
        $client = OpenDxp::getContainer()->get('opendxp.http_client');
        $requestType = 'GET';

        if (!isset($options['timeout'])) {
            $options['timeout'] = 5;
        }

        if (count($paramsGet) > 0) {
            //need to insert get params from url to $paramsGet because otherwise they would be ignored
            $urlParts = parse_url($url);

            if (isset($urlParts['query'])) {
                $urlParams = [];

                parse_str($urlParts['query'], $urlParams);

                if ($urlParams) {
                    $paramsGet = [...$urlParams, ...$paramsGet];
                }
            }

            $options[RequestOptions::QUERY] = $paramsGet;
        }

        if (count($paramsPost) > 0) {
            $options[RequestOptions::FORM_PARAMS] = $paramsPost;
            $requestType = 'POST';
        }

        try {
            $response = $client->request($requestType, $url, $options);

            if ($response->getStatusCode() < 300) {
                return (string)$response->getBody();
            }
        } catch (Exception) {
        }

        return false;
    }

    /**
     * @internal
     */
    public static function classExists(string $class): bool
    {
        return self::classInterfaceExists($class, 'class');
    }

    /**
     * @internal
     */
    public static function interfaceExists(string $class): bool
    {
        return self::classInterfaceExists($class, 'interface');
    }

    /**
     * @internal
     */
    public static function traitExists(string $class): bool
    {
        return self::classInterfaceExists($class, 'trait');
    }

    /**
     * @param string $type (e.g. 'class', 'interface', 'trait')
     */
    private static function classInterfaceExists(string $class, string $type): bool
    {
        $functionName = $type . '_exists';

        // if the class is already loaded we can skip right here
        if ($functionName($class, false)) {
            return true;
        }

        $class = '\\' . ltrim($class, '\\');

        // let's test if we have seens this class already before
        if (isset(self::$notFoundClassNames[$class])) {
            return false;
        }

        // we need to set a custom error handler here for the time being
        // unfortunately suppressNotFoundWarnings() doesn't work all the time, it has something to do with the calls in
        // OpenDxp\Tool::ClassMapAutoloader(), but don't know what actual conditions causes this problem.
        // but to be save we log the errors into the debug.log, so if anything else happens we can see it there
        // the normal warning is e.g. Warning: include_once(Path/To/Class.php): failed to open stream: No such file or directory in ...
        set_error_handler(fn (int $errno, string $errstr, string $errfile, int $errline): bool =>
            //Logger::debug(implode(" ", [$errno, $errstr, $errfile, $errline]));
            true);

        $exists = $functionName($class);

        restore_error_handler();

        if (!$exists) {
            self::$notFoundClassNames[$class] = true; // value doesn't matter, key lookups are faster ;-)
        }

        return $exists;
    }
}
