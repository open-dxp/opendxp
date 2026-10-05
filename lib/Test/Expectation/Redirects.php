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

namespace OpenDxp\Test\Expectation;

use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Bundle\SeoBundle\Redirect\RedirectHandler;
use Symfony\Component\HttpFoundation\Response;

/**
 * `toRedirectTo` checks that a response redirects to a location. A location that starts with a slash is compared with
 * the path and the query of the redirect, so a test does not depend on the host.
 *
 * `toBeAnsweredBy` checks which redirect answered a request, or that none did.
 */
final class Redirects
{
    public static function register(): void
    {
        expect()->extend('toRedirectTo', function (string $location) {
            expect($this->value)
                ->toBeInstanceOf(Response::class)
                ->and($this->value->isRedirect())
                ->toBeTrue()
                ->and(Redirects::comparableLocation((string) $this->value->headers->get('Location'), $location))
                ->toBe($location);

            return $this;
        });

        expect()->extend('toBeAnsweredBy', function (?Redirect $redirect) {
            expect($this->value)
                ->toBeInstanceOf(Response::class)
                ->and($this->value->headers->get(RedirectHandler::RESPONSE_HEADER_NAME_ID))
                ->toBe($redirect === null ? null : (string) $redirect->getId());

            return $this;
        });
    }

    public static function comparableLocation(string $actual, string $expected): string
    {
        if (!str_starts_with($expected, '/')) {
            return $actual;
        }

        $url = parse_url($actual);

        return ($url['path'] ?? '/') . (isset($url['query']) ? '?' . $url['query'] : '');
    }
}
