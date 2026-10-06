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

use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Model\Document;
use OpenDxp\Model\Site;

/**
 * @extends AbstractSavingFactory<Redirect>
 */
final class RedirectFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return Redirect::class;
    }

    public function forDomain(string $domain): static
    {
        return $this->with([
            'type'   => Redirect::TYPE_DOMAIN,
            'source' => $domain,
        ]);
    }

    public function matching(string $pattern): static
    {
        return $this->with([
            'source' => $pattern,
            'regex'  => true,
        ]);
    }

    public function forSite(Site $site): static
    {
        return $this->with(['sourceSite' => $site->getId()]);
    }

    public function toSite(Site $site): static
    {
        return $this->with(['targetSite' => $site->getId()]);
    }

    /**
     * A redirect names a target document by its id.
     */
    public function toDocument(Document $document): static
    {
        return $this->with(['target' => (string) $document->getId()]);
    }

    public function withStatusCode(int $statusCode): static
    {
        return $this->with(['statusCode' => $statusCode]);
    }

    public function withPriority(int $priority): static
    {
        return $this->with(['priority' => $priority]);
    }

    public function passingThroughPath(): static
    {
        return $this->with(['passThroughPath' => true]);
    }

    public function passingThroughParameters(): static
    {
        return $this->with(['passThroughParameters' => true]);
    }

    public function protected(): static
    {
        return $this->with(['protected' => true]);
    }

    public function inactive(): static
    {
        return $this->with(['active' => false]);
    }

    public function started(): static
    {
        return $this->with(static fn (): array => [
            'validFrom' => self::faker()->dateTimeBetween('-1 year', '-1 hour')->getTimestamp(),
        ]);
    }

    public function scheduled(): static
    {
        return $this->with(static fn (): array => [
            'validFrom' => self::faker()->dateTimeBetween('+1 hour', '+1 year')->getTimestamp(),
        ]);
    }

    public function expiring(): static
    {
        return $this->with(static fn (): array => [
            'expiry' => self::faker()->dateTimeBetween('+1 hour', '+1 year')->getTimestamp(),
        ]);
    }

    public function expired(): static
    {
        return $this->with(static fn (): array => [
            'expiry' => self::faker()->dateTimeBetween('-1 year', '-1 hour')->getTimestamp(),
        ]);
    }

    protected function defaults(): array
    {
        return [
            'source' => sprintf('/%s', self::faker()->unique()->slug()),
            'target' => sprintf('/%s', self::faker()->slug()),
        ];
    }
}
