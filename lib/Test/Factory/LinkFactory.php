<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Document;
use OpenDxp\Model\Document\Link;

/**
 * @extends AbstractDocumentFactory<Link>
 *
 * @method Link create(array|callable $attributes = [])
 * @method static Link createOne(array $attributes = [])
 * @method static list<Link> createMany(int $number, array $attributes = [])
 */
final class LinkFactory extends AbstractDocumentFactory
{
    public static function class(): string
    {
        return Link::class;
    }

    public function withTarget(Document $target): static
    {
        return $this->with(['internal' => $target->getId()]);
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'key'          => sprintf('link-%s', self::faker()->unique()->numerify('##########')),
            'type'         => 'link',
            'linktype'     => 'internal',
            'internalType' => 'document',
        ];
    }

    protected function initialize(): static
    {
        return parent::initialize()->withNavigationName();
    }
}
