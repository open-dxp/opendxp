<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Document;
use OpenDxp\Model\Site;

/**
 * @extends AbstractSavingFactory<Site>
 *
 * @method Site create(array|callable $attributes = [])
 * @method static Site createOne(array $attributes = [])
 * @method static list<Site> createMany(int $number, array $attributes = [])
 */
final class SiteFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return Site::class;
    }

    public function withRoot(Document $root): static
    {
        return $this->with(['rootId' => $root->getId()]);
    }

    /**
     * @param list<string> $domains the domains besides the main one
     */
    public function withDomains(array $domains): static
    {
        return $this->with(['domains' => $domains]);
    }

    /**
     * @param array<string, string> $localized one path per locale
     */
    public function withErrorDocuments(array $localized, ?string $default = null): static
    {
        $attributes = ['localizedErrorDocuments' => $localized];

        if ($default !== null) {
            $attributes['errorDocument'] = $default;
        }

        return $this->with($attributes);
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function withSettings(array $settings): static
    {
        return $this->with(['customSettings' => $settings]);
    }

    protected function defaults(): array
    {
        return [
            'mainDomain' => sprintf('site-%s.test', self::faker()->unique()->numerify('##########')),
        ];
    }

    protected function initialize(): static
    {
        return parent::initialize()->beforeInstantiate(
            static function (array $parameters): array {
                $parameters['rootId'] ??= PageFactory::createOne([
                    'key' => str_replace('.', '-', $parameters['mainDomain']),
                ])->getId();

                return $parameters;
            },
        );
    }
}
