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

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Translation;

/**
 * @extends AbstractSavingFactory<Translation>
 */
final class TranslationFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return Translation::class;
    }

    /**
     * @param array<string, string> $translations
     */
    public function withTranslations(array $translations): static
    {
        return $this->with(['translations' => $translations]);
    }

    public function inAdminDomain(): static
    {
        return $this->with(['domain' => Translation::DOMAIN_ADMIN]);
    }

    protected function defaults(): array
    {
        return [
            'key' => self::faker()->unique()->slug(2),
        ];
    }
}
