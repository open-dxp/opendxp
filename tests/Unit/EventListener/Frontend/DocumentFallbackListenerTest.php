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


namespace OpenDxp\Tests\Unit\EventListener\Frontend;

use OpenDxp\Bundle\CoreBundle\EventListener\Frontend\DocumentFallbackListener;
use OpenDxp\Http\Request\Resolver\DocumentResolver;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use OpenDxp\Http\Request\Resolver\SiteResolver;
use OpenDxp\Model\Document;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

it('keeps the locale the route asked for when it falls back to the nearest document', function () {

    $document = $this->createMock(Document\Page::class);
    $document->method('getProperty')->with('language')->willReturn('de');

    $documents = $this->createMock(Document\Service::class);
    $documents->method('getNearestDocumentByPath')->willReturn($document);

    $context = $this->createMock(OpenDxpContextResolver::class);
    $context->method('matchesOpenDxpContext')->willReturn(true);

    $requests = new RequestStack();
    $resolver = new DocumentResolver($requests);

    $listener = new DocumentFallbackListener($requests, $resolver, $this->createMock(SiteResolver::class), $documents);
    $listener->setOpenDxpContextResolver($context);

    $kernel = $this->createMock(HttpKernelInterface::class);
    $request = Request::create('/de/product/it');
    $requests->push($request);

    $listener->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

    // Symfony's own locale listener has run by now and taken the locale from the route.
    $request->setLocale('it');

    $listener->onKernelController(new ControllerEvent(
        $kernel,
        static fn () => new Response(),
        $request,
        HttpKernelInterface::MAIN_REQUEST,
    ));

    expect($resolver->getDocument($request))
        ->toBe($document)
        ->and($request->getLocale())
        ->toBe('it');
});
