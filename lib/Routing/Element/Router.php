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

namespace OpenDxp\Routing\Element;

use OpenDxp\Http\RequestHelper;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\ElementInterface;
use Symfony\Cmf\Component\Routing\VersatileGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * A custom router implementation handling opendxp elements.
 */
class Router implements RouterInterface, RequestMatcherInterface, VersatileGeneratorInterface
{
    public function __construct(protected RequestContext $context, protected RequestHelper $requestHelper)
    {
    }

    public function setContext(RequestContext $context): void
    {
        $this->context = $context;
    }

    public function getContext(): RequestContext
    {
        return $this->context;
    }

    public function getRouteDebugMessage(string $name, array $parameters = []): string
    {
        $element = $parameters['element'] ?? null;
        if ($element instanceof ElementInterface) {
            return sprintf('opendxp_element (Type: %s, ID: %d)', $element->getType(), $element->getId());
        }

        return 'opendxp_element (No element)';
    }

    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        if ($name !== 'opendxp_element') {
            throw new RouteNotFoundException('Not supported name');
        }
        $element = $parameters['element'] ?? null;
        unset($parameters['element']);
        if ($element instanceof Document || $element instanceof Asset) {
            $schemeAuthority = '';
            $host = $this->context->getHost();
            $scheme = $this->context->getScheme();
            $path = $element->getFullPath();
            $needsHostname = self::ABSOLUTE_URL === $referenceType || self::NETWORK_PATH === $referenceType;

            if (str_contains($path, '://')) {
                $host = parse_url($path, PHP_URL_HOST);
                $scheme = parse_url($path, PHP_URL_SCHEME);
                $path = parse_url($path, PHP_URL_PATH);
                $needsHostname = true;
            }

            if ($needsHostname && ('' !== $host || '' !== $scheme && 'http' !== $scheme && 'https' !== $scheme)) {
                $port = '';
                if ('http' === $scheme && 80 !== $this->context->getHttpPort()) {
                    $port = ':'.$this->context->getHttpPort();
                } elseif ('https' === $scheme && 443 !== $this->context->getHttpsPort()) {
                    $port = ':'.$this->context->getHttpsPort();
                }
                $schemeAuthority = self::NETWORK_PATH === $referenceType || '' === $scheme ? '//' : "$scheme://";
                $schemeAuthority .= $host.$port;
            }

            $qs = http_build_query($parameters);
            if ($qs) {
                $qs = '?' . $qs;
            }

            return $schemeAuthority . $this->context->getBaseUrl() . $path . $qs;
        }
        if ($element instanceof Concrete) {
            $linkGenerator = $element->getClass()->getLinkGenerator();
            if ($linkGenerator) {
                return $linkGenerator->generate($element, [
                    'route' => $this->getCurrentRoute(),
                    'parameters' => $parameters,
                    'context' => $this,
                    'referenceType' => $referenceType,
                ]);
            }
        }

        if ($element instanceof ElementInterface) {
            throw new RouteNotFoundException(
                sprintf(
                    'Could not generate URL for element (Type: %s, ID: %d)',
                    $element->getType(),
                    $element->getId()
                )
            );
        }

        throw new RouteNotFoundException('Could not generate URL for non elements');
    }

    /**
     * Tries to get the current route name from current or main request
     */
    protected function getCurrentRoute(): ?string
    {
        $route = null;

        if ($this->requestHelper->hasCurrentRequest()) {
            $route = $this->requestHelper->getCurrentRequest()->attributes->get('_route');
        }

        if (!$route && $this->requestHelper->hasMainRequest()) {
            return $this->requestHelper->getMainRequest()->attributes->get('_route');
        }

        return $route;
    }

    public function matchRequest(Request $request): array
    {
        throw new ResourceNotFoundException(sprintf('No routes found for "%s".', $request->getPathInfo()));
    }

    public function match(string $pathinfo): array
    {
        throw new ResourceNotFoundException(sprintf('No routes found for "%s".', $pathinfo));
    }

    public function getRouteCollection(): RouteCollection
    {
        return new RouteCollection();
    }
}
