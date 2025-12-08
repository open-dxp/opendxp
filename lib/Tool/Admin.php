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

namespace OpenDxp\Tool;

use Exception;
use Locale;
use OpenDxp;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model\User;
use OpenDxp\Security\User\TokenStorageUserResolver;
use OpenDxp\Tool\Text\Csv;
use stdClass;

/**
 * @internal
 */
class Admin
{
    /**
     * finds installed languages
     */
    public static function getLanguages(): array
    {
        $baseResource = OpenDxp::getContainer()->getParameter('opendxp_admin.translations.path');
        $languageDir = OpenDxp::getKernel()->locateResource($baseResource);
        $adminLanguages = OpenDxp::getContainer()->getParameter('opendxp_admin.admin_languages');
        $appDefaultPath = OpenDxp::getContainer()->getParameter('translator.default_path');

        $languageDirs = [$languageDir, $appDefaultPath];
        $translatedLanguages = [];
        foreach ($languageDirs as $filesDir) {
            if (is_dir($filesDir)) {
                $files = scandir($filesDir);
                foreach ($files as $file) {
                    if (is_file($filesDir . '/' . $file)) {
                        $parts = explode('.', $file);

                        $languageCode = $parts[0];
                        if ($parts[0] === 'admin') {
                            // this is for the app specific translations
                            $languageCode = $parts[1];
                        }

                        if (($parts[1] === 'json' || $parts[0] === 'admin') && OpenDxp::getContainer()->get(LocaleServiceInterface::class)->isLocale($languageCode)) {
                            $translatedLanguages[] = $languageCode;
                        }
                    }
                }
            }
        }

        $languages = [];
        foreach ($adminLanguages as $adminLanguage) {
            if (in_array($adminLanguage, $translatedLanguages, true) || in_array(Locale::getPrimaryLanguage($adminLanguage), $translatedLanguages, true)) {
                $languages[] = $adminLanguage;
            }
        }

        if ($languages === []) {
            $languages = $translatedLanguages;
        }

        return array_unique($languages);
    }

    public static function getMinimizedScriptPath(string $scriptContent): array
    {
        $scriptPath = 'minified_javascript_core_'.md5($scriptContent).'.js';

        $storage = Storage::get('admin');
        $storage->write($scriptPath, $scriptContent);

        return [
            'storageFile' => basename($scriptPath),
            '_dc' => \OpenDxp\Version::getRevision(),
        ];
    }

    public static function determineCsvDialect(string $file): stdClass
    {
        // minimum 10 lines, to be sure take more
        $sample = '';
        for ($i = 0; $i < 10; $i++) {
            $sample .= implode('', array_slice(file($file), 0, 11)); // grab 20 lines
        }

        try {
            $sniffer = new Csv();
            $dialect = $sniffer->detect($sample);
        } catch (Exception) {
            // use default settings
            $dialect = new stdClass();
            $dialect->delimiter = ';';
            $dialect->quotechar = '"';
            $dialect->escapechar = '\\';
        }

        // validity check
        if (!in_array($dialect->delimiter, [';', ',', "\t", '|', ':'])) {
            $dialect->delimiter = ';';
        }

        return $dialect;
    }

    public static function getCurrentUser(): ?User
    {
        return OpenDxp::getContainer()
            ->get(TokenStorageUserResolver::class)
            ->getUser();
    }

    public static function reorderWebsiteLanguages(User $user, array|string $languages, bool $returnLanguageArray = false): array|string
    {
        if (!is_array($languages)) {
            $languages = explode(',', $languages);
        }

        $contentLanguages = $user->getContentLanguages();
        if ($contentLanguages) {
            $contentLanguages = array_intersect($contentLanguages, $languages);
            $newLanguages = array_diff($languages, $contentLanguages);
            $languages = [...$contentLanguages, ...$newLanguages];
        }

        if (in_array('default', $languages)) {
            $languages = array_diff($languages, ['default']);
            array_unshift($languages, 'default');
        }
        if ($returnLanguageArray) {
            return $languages;
        }

        return implode(',', $languages);
    }
}
