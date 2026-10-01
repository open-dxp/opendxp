<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Document\Snippet;

/**
 * @extends AbstractPageSnippetFactory<Snippet>
 *
 * @method Snippet create(array|callable $attributes = [])
 * @method static Snippet createOne(array $attributes = [])
 * @method static list<Snippet> createMany(int $number, array $attributes = [])
 */
final class SnippetFactory extends AbstractPageSnippetFactory
{
    public static function class(): string
    {
        return Snippet::class;
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'key'  => sprintf('snippet-%s', self::faker()->unique()->numerify('##########')),
            'type' => 'snippet',
        ];
    }
}
