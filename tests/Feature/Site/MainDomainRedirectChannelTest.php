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

use OpenDxp\Bundle\CoreBundle\EventListener\Frontend\RoutingListener;
use OpenDxp\Config;
use OpenDxp\Http\Request\Host\GeneralHostProviderInterface;
use OpenDxp\Http\Request\Host\GeneralHostResolver;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use OpenDxp\Http\Request\Resolver\SiteResolver;
use OpenDxp\Http\RequestHelper;
use OpenDxp\SystemSettingsConfig;
use OpenDxp\TestFoundation\Container;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

beforeEach(function () {
    $this->originalSettings = SystemSettingsConfig::get();
    $settings = $this->originalSettings;
    $settings['general']['redirect_to_maindomain'] = true;
    Container::get(SystemSettingsConfig::class)->testSave($settings);

    $this->provider = new class implements GeneralHostProviderInterface {
        /**
         * @var list<array<string, mixed>>
         */
        public array $contexts = [];

        public function provide(array $context = []): ?string
        {
            $this->contexts[] = $context;

            return 'general.channel-test.example';
        }
    };
});

afterEach(function () {
    Container::get(SystemSettingsConfig::class)->testSave($this->originalSettings);
});

function redirectRequestIn(string $openDxpContext, GeneralHostProviderInterface $provider, Request $request): RequestEvent
{
    $contextResolver = test()->createMock(OpenDxpContextResolver::class);
    $contextResolver
        ->method('matchesOpenDxpContext')
        ->willReturnCallback(fn (Request $request, array|string $context) => $context === $openDxpContext);

    $listener = new RoutingListener(
        test()->createMock(RequestHelper::class),
        test()->createMock(SiteResolver::class),
        new Config(),
        new GeneralHostResolver([$provider]),
    );
    $listener->setOpenDxpContextResolver($contextResolver);
    $listener->setLogger(new NullLogger());

    $event = new RequestEvent(test()->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    $listener->onKernelRequest($event);

    return $event;
}

it('asks the general host providers with the admin channel for the redirect in the admin context', function () {
    $request = Request::create('https://admin.channel-test.example/admin/login');

    $event = redirectRequestIn(OpenDxpContextResolver::CONTEXT_ADMIN, $this->provider, $request);

    expect($this->provider->contexts)
        ->toBe([[
            GeneralHostProviderInterface::CONTEXT_SOURCE => $request,
            GeneralHostProviderInterface::CONTEXT_CHANNEL => GeneralHostProviderInterface::CHANNEL_ADMIN,
        ]])
        ->and($event->getResponse())
        ->toBeInstanceOf(RedirectResponse::class)
        ->and($event->getResponse()->getTargetUrl())
        ->toBe('https://general.channel-test.example/admin/login');
});

it('asks the general host providers without a channel for the redirect in the default context', function () {
    $request = Request::create('https://unknown.channel-test.example/some/page');

    redirectRequestIn(OpenDxpContextResolver::CONTEXT_DEFAULT, $this->provider, $request);

    expect($this->provider->contexts)->toBe([[GeneralHostProviderInterface::CONTEXT_SOURCE => $request]]);
});
