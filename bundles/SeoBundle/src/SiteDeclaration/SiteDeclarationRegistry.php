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

namespace OpenDxp\Bundle\SeoBundle\SiteDeclaration;

use InvalidArgumentException;
use LogicException;

final class SiteDeclarationRegistry
{
    public const string TAG = 'opendxp_seo.site_declaration';

    /**
     * @var array<string, SiteDeclarationInterface>|null
     */
    private ?array $enabledDeclarations = null;

    /**
     * @param iterable<SiteDeclarationInterface> $declarations
     * @param list<string> $enabledNames
     */
    public function __construct(
        private readonly iterable $declarations,
        private readonly array $enabledNames,
    ) {
    }

    /**
     * Returns the enabled declarations by name.
     *
     * @return array<string, SiteDeclarationInterface>
     */
    public function getDeclarations(): array
    {
        return $this->enabledDeclarations ??= $this->loadEnabledDeclarations();
    }

    public function hasDeclaration(string $name): bool
    {
        return isset($this->getDeclarations()[$name]);
    }

    public function getDeclaration(string $name): SiteDeclarationInterface
    {
        return $this->getDeclarations()[$name] ?? throw new InvalidArgumentException(
            sprintf('No site declaration is enabled as "%s".', $name),
        );
    }

    /**
     * @return array<string, SiteDeclarationInterface>
     */
    private function loadEnabledDeclarations(): array
    {
        $declarations = [];

        foreach ($this->declarations as $declaration) {
            $name = $declaration->getName();

            if (strlen($name) > 33 || preg_match('/^[a-z0-9]+(?:_[a-z0-9]+)*$/', $name) !== 1) {
                throw new LogicException(sprintf(
                    'The site declaration %s has the name "%s". A name is snake case and has at most 33 characters.',
                    $declaration::class,
                    $name,
                ));
            }

            if (isset($declarations[$name])) {
                throw new LogicException(sprintf(
                    'The site declarations %s and %s both use the name "%s".',
                    $declarations[$name]::class,
                    $declaration::class,
                    $name,
                ));
            }

            $declarations[$name] = $declaration;
        }

        $enabledDeclarations = [];
        $namesByPath = [];

        foreach ($this->enabledNames as $name) {
            $declaration = $declarations[$name] ?? throw new LogicException(sprintf(
                'No site declaration has the name "%s". Check opendxp_seo.site_declarations.',
                $name,
            ));

            $path = $declaration->getPath();

            if (isset($namesByPath[$path])) {
                throw new LogicException(sprintf(
                    'The site declarations "%s" and "%s" both use the path %s.',
                    $namesByPath[$path],
                    $name,
                    $path,
                ));
            }

            $namesByPath[$path] = $name;
            $enabledDeclarations[$name] = $declaration;
        }

        return $enabledDeclarations;
    }
}
