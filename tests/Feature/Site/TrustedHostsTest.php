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

namespace OpenDxp\Tests\Feature\Site;

use OpenDxp\Bundle\CoreBundle\EventListener\TrustedHostListener;
use OpenDxp\Http\Request\Host\TrustedHostPatternProviderInterface;
use OpenDxp\Http\Request\Host\TrustedHosts;
use OpenDxp\Test\Factory\SiteFactory;
use OpenDxp\TestFoundation\Container;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

beforeEach(function () {
    SiteFactory::new()
        ->withDomains([
            'www.shop.test',
            '*.campaign.test',
        ])
        ->create(['mainDomain' => 'shop.test']);
});

it('trusts the hosts of the installation', function (string $host) {
    $trustedHosts = new TrustedHosts([
        'admin.example.test',
        '*.backend.test',
    ]);

    expect($trustedHosts->isTrusted($host))->toBeTrue();
})->with([
    'the general domain' => ['opendxp-testing.test'],
    'the main domain of a site' => ['shop.test'],
    'an additional domain of a site' => ['www.shop.test'],
    'a wildcard domain of a site' => ['summer.campaign.test'],
    'a configured host' => ['admin.example.test'],
    'a subdomain of a configured wildcard' => ['staging.backend.test'],
    'a host in capital letters' => ['SHOP.test'],
]);

it('does not trust a host outside of the installation', function (string $host) {
    $trustedHosts = new TrustedHosts([
        'admin.example.test',
        '*.backend.test',
    ]);

    expect($trustedHosts->isTrusted($host))->toBeFalse();
})->with([
    'an unknown host' => ['attacker.test'],
    'the domain of a configured wildcard' => ['backend.test'],
    'a host that only ends like a configured wildcard' => ['evilbackend.test'],
    'a subdomain of a configured host' => ['x.admin.example.test'],
    'an empty host' => [''],
]);

it('trusts a host that a tagged pattern provider derives from the known hosts', function () {
    $provider = new class implements TrustedHostPatternProviderInterface {
        public function getPatterns(array $knownHostPatterns): array
        {
            return array_map(
                static fn (string $pattern): string => sprintf('^backoffice\\.%s', substr($pattern, 1)),
                $knownHostPatterns,
            );
        }
    };
    $trustedHosts = new TrustedHosts(patternProviders: [$provider]);

    $trusted = $trustedHosts->isTrusted('backoffice.shop.test');

    expect($trusted)->toBeTrue();
});

it('answers a request to an unknown host while the check is off', function () {
    $response = answerTo('http://attacker.test/');

    expect($response->getStatusCode())->not->toBe(400);
});

it('refuses a request to an unknown host while the check is on', function () {
    $listener = new TrustedHostListener(Container::get(TrustedHosts::class));
    $event = new RequestEvent(
        Container::get(HttpKernelInterface::class),
        Request::create('http://attacker.test/'),
        HttpKernelInterface::MAIN_REQUEST,
    );

    $listener->onKernelRequest($event);

    expect(fn () => $event->getRequest()->getHost())->toThrow(SuspiciousOperationException::class);
})->after(fn () => Request::setTrustedHosts([]));

it('leaves the check to framework.trusted_hosts when a project sets it', function () {
    Request::setTrustedHosts(['^legacy\\.test$']);
    $listener = new TrustedHostListener(Container::get(TrustedHosts::class));
    $event = new RequestEvent(
        Container::get(HttpKernelInterface::class),
        Request::create('http://legacy.test/'),
        HttpKernelInterface::MAIN_REQUEST,
    );

    $listener->onKernelRequest($event);

    expect($event->getRequest()->getHost())
        ->toBe('legacy.test')
        ->and(fn () => Request::create('http://shop.test/')->getHost())
        ->toThrow(SuspiciousOperationException::class);
})->after(fn () => Request::setTrustedHosts([]));
