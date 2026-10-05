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

use OpenDxp\Model\Document;

/**
 * @template T of Document
 *
 * @extends AbstractElementFactory<T>
 */
abstract class AbstractDocumentFactory extends AbstractElementFactory
{
    private const int LINK = -2000;

    public function withTranslationOf(Document $source, ?string $locale = null): static
    {
        return $this->afterInstantiate(
            static fn (Document $document) => (new Document\Service())
                ->addTranslation($source, $document, $locale),
            self::LINK,
        );
    }

    public function withNavigationName(?string $name = null): static
    {
        return $this->afterInstantiate(
            static function (Document $document) use ($name): void {
                $title = $name ?? $document->getKey();

                $document->setProperty('navigation_title', 'text', $title);
                $document->setProperty('navigation_name', 'text', $title);
            },
        );
    }

    public function unpublished(): static
    {
        return $this->with(['published' => false]);
    }

    public function withLocale(string $locale): static
    {
        return $this->afterInstantiate(
            static function (Document $document) use ($locale): void {
                $document->setProperty('language', 'text', $locale, false, true);
            },
        );
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'published' => true,
        ];
    }
}
