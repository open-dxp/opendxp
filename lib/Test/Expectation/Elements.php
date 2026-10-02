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


namespace OpenDxp\Test\Expectation;

use DateTimeInterface;
use OpenDxp;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\AbstractObject;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Localizedfield;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Property;
use OpenDxp\Tool;
use RuntimeException;

/**
 * Registers `toEqualElement`, which compares two elements by a fingerprint of their own data
 * instead of by identity. A copy therefore equals the element it was copied from, and passing
 * `ignoringCopyDifferences` leaves out what a copy is expected to differ in.
 */
final class Elements
{
    public static function register(): void
    {
        expect()->extend('toEqualElement', function (ElementInterface $expected, bool $ignoringCopyDifferences = false) {
            expect(Elements::fingerprint($this->value, $ignoringCopyDifferences))
                ->toBe(Elements::fingerprint($expected, $ignoringCopyDifferences));

            return $this;
        });
    }

    public static function fingerprint(ElementInterface $element, bool $ignoringCopyDifferences = false): string
    {
        $parts = match (true) {
            $element instanceof Asset => self::ofAsset($element),
            $element instanceof Document => self::ofDocument($element),
            $element instanceof AbstractObject => self::ofObject($element),
            default => throw new RuntimeException(sprintf('%s is not an element that can be compared.', $element::class)),
        };

        if (!$ignoringCopyDifferences) {
            $parts = [...$parts, ...self::ofIdentity($element)];
        }

        $parts['userOwner'] = (string) $element->getUserOwner();

        return implode(',', [...$parts, ...self::ofProperties($element->getProperties())]);
    }

    /**
     * What a copy of an element differs in: where it sits, what it is called, and when it was made.
     */
    private static function ofIdentity(ElementInterface $element): array
    {
        return [
            'key' => (string) $element->getKey(),
            'id' => (string) $element->getId(),
            'path' => (string) $element->getPath(),
            'parentId' => (string) $element->getParentId(),
            'creation' => (string) $element->getCreationDate(),
            'modification' => (string) $element->getModificationDate(),
            'userModified' => (string) $element->getUserModification(),
        ];
    }

    private static function ofAsset(Asset $asset): array
    {
        $parts = ['customSettings' => serialize($asset->getCustomSettings())];

        if ($asset->getData()) {
            $parts['data'] = base64_encode($asset->getData());
        }

        return $parts;
    }

    private static function ofDocument(Document $document): array
    {
        $parts = [];

        if ($document instanceof Document\PageSnippet) {
            $editables = $document->getEditables();
            ksort($editables);

            foreach ($editables as $name => $editable) {
                $parts['editable_' . $name] = match (true) {
                    // A video editable renders a random id, so only its own identity can be compared.
                    $editable instanceof Document\Editable\Video => sprintf('%s:%s_%s', $editable->getName(), $editable->getType(), $editable->getId()),
                    $editable instanceof Document\Editable\Block => $editable->getName(),
                    default => sprintf('%s:%s', $editable->getName(), $editable->frontend()),
                };
            }

            if ($document instanceof Document\Page) {
                $parts['title'] = (string) $document->getTitle();
                $parts['description'] = (string) $document->getDescription();
            }

            $parts['published'] = var_export($document->isPublished(), true);
        }

        if ($document instanceof Document\Link) {
            $parts['link'] = $document->getHtml();
        }

        return $parts;
    }

    private static function ofObject(AbstractObject $object): array
    {
        if (!$object instanceof Concrete) {
            return [];
        }

        $parts = [];

        foreach ($object->getClass()->getFieldDefinitions() as $name => $definition) {
            $parts[$name] = self::ofField($name, $definition, $object);
        }

        $parts['published'] = var_export($object->isPublished(), true);

        return $parts;
    }

    private static function ofField(string $name, Data $definition, Concrete $object): string
    {
        $getter = 'get' . ucfirst($name);

        if (!method_exists($object, $getter)) {
            return '';
        }

        if ($definition instanceof Data\Fieldcollections) {
            return self::ofFieldCollection($object->{$getter}());
        }

        if ($definition instanceof Data\Localizedfields) {
            return self::ofLocalizedFields($definition, $object, $object->{$getter}());
        }

        // A password never leaves the object, and a reverse relation belongs to the other side.
        if ($definition instanceof Data\Password || $definition instanceof Data\ReverseObjectRelation) {
            return '';
        }

        return self::flatten($definition->getForCsvExport($object));
    }

    private static function ofFieldCollection(mixed $collection): string
    {
        if (!$collection instanceof OpenDxp\Model\DataObject\Fieldcollection) {
            return '';
        }

        $items = [];

        foreach ($collection->getItems() as $position => $item) {
            foreach ($item->getDefinition()->getFieldDefinitions() as $name => $definition) {
                $items[$position][$name] = $definition instanceof Data\Password
                    ? null
                    : $definition->getForCsvExport($item);
            }
        }

        return serialize($items);
    }

    private static function ofLocalizedFields(Data\Localizedfields $definition, Concrete $object, mixed $fields): string
    {
        if (!$fields instanceof Localizedfield) {
            return '';
        }

        $locales = OpenDxp::getContainer()->get(LocaleServiceInterface::class);
        $before = $locales->getLocale();
        $perLanguage = [];

        foreach (Tool::getValidLanguages() as $language) {
            $locales->setLocale($language);

            foreach ($definition->getFieldDefinitions() as $nested) {
                $perLanguage[$language][$nested->getName()] = self::ofField($nested->getName(), $nested, $object);
            }
        }

        $locales->setLocale($before);

        return serialize($perLanguage);
    }

    private static function ofProperties(array $properties): array
    {
        ksort($properties);
        $parts = [];

        foreach ($properties as $name => $property) {
            $label = sprintf('property_%s_%s', $name, $property->getType());
            $value = self::ofProperty($property);

            if ($value !== null) {
                $parts[$label] = sprintf('%s:%s', $label, $value);
            }
        }

        return $parts;
    }

    private static function ofProperty(Property $property): ?string
    {
        $data = $property->getData();

        return match ($property->getType()) {
            'document', 'asset', 'object' => $data instanceof ElementInterface ? (string) $data->getId() : ' null',
            'date' => $data instanceof DateTimeInterface ? (string) $data->getTimestamp() : null,
            'bool' => var_export((bool) $data, true),
            'text', 'select' => (string) $data,
            default => throw new RuntimeException(sprintf('A property of type %s cannot be compared.', $property->getType())),
        };
    }

    private static function flatten(mixed $value): string
    {
        return is_scalar($value) || $value === null ? (string) $value : serialize($value);
    }
}
