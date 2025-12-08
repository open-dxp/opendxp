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

namespace OpenDxp\Http\Request\Resolver;

use InvalidArgumentException;
use OpenDxp\Http\Context\OpenDxpContextGuesser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Gets/sets and guesses opendxp context (admin, default) from request. The guessing is implemented in OpenDxpContextGuesser
 * and matches the request against a list of paths and routes which are exposed via config.
 */
class OpenDxpContextResolver extends AbstractRequestResolver
{
    const ATTRIBUTE_OPENDXP_CONTEXT = '_opendxp_context';

    const CONTEXT_ADMIN = 'admin';

    const CONTEXT_DEFAULT = 'default';

    public function __construct(RequestStack $requestStack, protected OpenDxpContextGuesser $guesser)
    {
        parent::__construct($requestStack);
    }

    /**
     * Get opendxp context from request
     *
     *
     */
    public function getOpenDxpContext(?Request $request = null): ?string
    {
        if (!$request instanceof \Symfony\Component\HttpFoundation\Request) {
            $request = $this->getCurrentRequest();
        }

        $context = $request->attributes->get(self::ATTRIBUTE_OPENDXP_CONTEXT);

        if (!$context) {
            $context = $this->guesser->guess($request, static::CONTEXT_DEFAULT);
            $this->setOpenDxpContext($request, $context);
        }

        return $context;
    }

    /**
     * Sets the opendxp context on the request
     *
     */
    public function setOpenDxpContext(Request $request, string $context): void
    {
        $request->attributes->set(self::ATTRIBUTE_OPENDXP_CONTEXT, $context);
    }

    /**
     * Tests if the request matches a given contect. $context can also be an array of contexts. If one
     * of the contexts matches, the method will return true.
     *
     *
     */
    public function matchesOpenDxpContext(Request $request, array|string $context): bool
    {
        if (!is_array($context)) {
            $context = empty($context) ? [] : [$context];
        }

        if ($context === []) {
            throw new InvalidArgumentException('Can\'t match against empty opendxp context');
        }

        $resolvedContext = $this->getOpenDxpContext($request);
        if (!$resolvedContext) {
            // no context available to match -> false
            return false;
        }
        return in_array($resolvedContext, $context, true);
    }
}
