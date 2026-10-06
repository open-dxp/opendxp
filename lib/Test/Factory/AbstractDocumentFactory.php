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
    public function unpublished(): static
    {
        return $this->with(['published' => false]);
    }

    public function withLocale(string $locale): static
    {
        return $this->afterInstantiate(
            static function (Document $document) use ($locale): void {
                $document->setProperty('language', 'text', $locale, inheritable: true);
            },
        );
    }

    /**
     * The translation takes its language from withLocale().
     */
    public function withTranslationOf(Document $source): static
    {
        return $this->afterWriting(
            static function (Document $document) use ($source): void {
                (new Document\Service())->addTranslation($source, $document);
            },
        );
    }

    public function withNavigationName(string $name): static
    {
        return $this->afterInstantiate(
            static function (Document $document) use ($name): void {
                self::nameInNavigation($document, $name);
            },
        );
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'key'       => self::faker()->unique()->slug(),
            'published' => true,
        ];
    }

    protected function initialize(): static
    {
        return parent::initialize()
            ->afterInstantiate(self::nameInNavigationByKey(...));
    }

    /**
     * The navigation leaves out a document without a navigation name. Only these types appear in it.
     */
    private static function nameInNavigationByKey(Document $document): void
    {
        $key = $document->getKey();

        if ($key === null) {
            return;
        }

        if ($document instanceof Document\Page
            || $document instanceof Document\Link
            || $document instanceof Document\Hardlink
        ) {
            self::nameInNavigation($document, $key);
        }
    }

    private static function nameInNavigation(Document $document, string $name): void
    {
        $document->setProperty('navigation_title', 'text', $name);
        $document->setProperty('navigation_name', 'text', $name);
    }
}
