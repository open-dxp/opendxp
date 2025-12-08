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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Http\Context;

use OpenDxp\Http\RequestMatcherFactory;
use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class OpenDxpContextGuesser
{
    private array $routes = [];

    private ?array $matchers = null;

    public function __construct(private readonly RequestMatcherFactory $requestMatcherFactory)
    {
    }

    /**
     * Add context specific routes
     *
     */
    public function addContextRoutes(string $context, array $routes): void
    {
        $this->routes[$context] = $routes;
    }

    /**
     * Guess the opendxp context
     *
     */
    public function guess(Request $request, string $default): string
    {
        foreach ($this->getMatchers() as $context => $matchers) {
            /** @var array $matcher */
            foreach ($matchers as $matcher) {
                $chainRequestMatcher = new ChainRequestMatcher($matcher);
                if ($chainRequestMatcher->matches($request)) {
                    return $context;
                }
            }
        }

        return $default;
    }

    /**
     * Get request matchers to query admin opendxp context from
     *
     */
    private function getMatchers(): array
    {
        if (null === $this->matchers) {
            $this->matchers = [];

            foreach ($this->routes as $context => $routes) {
                $this->matchers[$context] = $this->requestMatcherFactory->buildRequestMatchers($routes);
            }
        }

        return $this->matchers;
    }
}
