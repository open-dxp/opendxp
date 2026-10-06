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

use OpenDxp\Model\Document;
use OpenDxp\Model\Site;

/**
 * @extends AbstractSavingFactory<Site>
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
     * @param list<string> $domains
     */
    public function withDomains(array $domains): static
    {
        return $this->with(['domains' => $domains]);
    }

    public function withErrorDocument(string $path): static
    {
        return $this->with(['errorDocument' => $path]);
    }

    /**
     * @param array<string, string> $paths
     */
    public function withLocalizedErrorDocuments(array $paths): static
    {
        return $this->with(['localizedErrorDocuments' => $paths]);
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function withCustomSettings(array $settings): static
    {
        return $this->with(['customSettings' => $settings]);
    }

    protected function defaults(): array
    {
        return [
            'mainDomain' => sprintf('%s.test', self::faker()->unique()->domainWord()),
        ];
    }

    /**
     * A site cannot be written without its root document. An unsaved site gets none.
     */
    protected function initialize(): static
    {
        return parent::initialize()
            ->beforeInstantiate(
                static function (array $parameters, string $class, self $factory): array {
                    if ($factory->writes() && !isset($parameters['rootId'])) {
                        $root = DocumentPageFactory::createOne([
                            'key' => str_replace('.', '-', $parameters['mainDomain']),
                        ]);
                        $parameters['rootId'] = $root->getId();
                    }

                    return $parameters;
                },
            );
    }
}
