<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Document\Page;

/**
 * @extends AbstractPageSnippetFactory<Page>
 *
 * @method Page create(array|callable $attributes = [])
 * @method static Page createOne(array $attributes = [])
 * @method static list<Page> createMany(int $number, array $attributes = [])
 */
final class DocumentPageFactory extends AbstractPageSnippetFactory
{
    public static function class(): string
    {
        return Page::class;
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'key' => sprintf('page-%s', self::faker()->unique()->numerify('##########')),
        ];
    }
}
