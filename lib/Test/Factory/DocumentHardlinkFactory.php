<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Document;
use OpenDxp\Model\Document\Hardlink;

/**
 * @extends AbstractDocumentFactory<Hardlink>
 *
 * @method Hardlink create(array|callable $attributes = [])
 * @method static Hardlink createOne(array $attributes = [])
 * @method static list<Hardlink> createMany(int $number, array $attributes = [])
 */
final class DocumentHardlinkFactory extends AbstractDocumentFactory
{
    public static function class(): string
    {
        return Hardlink::class;
    }

    public function withSource(Document $source): static
    {
        return $this->with([
            'sourceId'             => $source->getId(),
            'propertiesFromSource' => true,
            'childrenFromSource'   => true,
        ]);
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'key'  => sprintf('hardlink-%s', self::faker()->unique()->numerify('##########')),
            'type' => 'hardlink',
        ];
    }
}
