<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Asset\Folder;

/**
 * @extends AbstractElementFactory<Folder>
 *
 * @method Folder create(array|callable $attributes = [])
 * @method static Folder createOne(array $attributes = [])
 * @method static list<Folder> createMany(int $number, array $attributes = [])
 */
final class AssetFolderFactory extends AbstractElementFactory
{
    public static function class(): string
    {
        return Folder::class;
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'filename' => sprintf('asset-folder-%s', uniqid()),
            'type'     => 'folder',
        ];
    }
}
