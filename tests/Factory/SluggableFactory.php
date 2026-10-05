<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Factory;

use OpenDxp\Model\DataObject\Sluggable;
use OpenDxp\Test\Factory\AbstractDataObjectFactory;

final class SluggableFactory extends AbstractDataObjectFactory
{
    public static function class(): string
    {
        return Sluggable::class;
    }

    /**
     * @param array<string, string> $names one name per language
     */
    public function withLocalizedNames(array $names): static
    {
        return $this->afterInstantiate(static function (Sluggable $object) use ($names): void {
            foreach ($names as $language => $name) {
                $object->setLname($name, $language);
            }
        });
    }
}
