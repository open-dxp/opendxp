<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Translation;

/**
 * @extends AbstractSavingFactory<Translation>
 *
 * @method Translation create(array|callable $attributes = [])
 * @method static Translation createOne(array $attributes = [])
 * @method static list<Translation> createMany(int $number, array $attributes = [])
 */
final class TranslationFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return Translation::class;
    }

    /**
     * @param array<string, string> $translations one per language
     */
    public function withTranslations(array $translations): static
    {
        return $this->with(['translations' => $translations]);
    }

    public function admin(): static
    {
        return $this->with(['domain' => Translation::DOMAIN_ADMIN]);
    }

    protected function defaults(): array
    {
        return [
            'key'    => sprintf('key.%s', uniqid()),
            'domain' => Translation::DOMAIN_DEFAULT,
        ];
    }
}
