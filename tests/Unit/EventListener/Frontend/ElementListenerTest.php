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

use OpenDxp\Bundle\CoreBundle\EventListener\Frontend\ElementListener;
use OpenDxp\Http\Request\Resolver\DocumentResolver;
use OpenDxp\Http\Request\Resolver\EditmodeResolver;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use OpenDxp\Http\RequestHelper;
use OpenDxp\Model\Document;
use OpenDxp\Security\User\UserLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

it('keeps the locale the route asked for over the language of the document', function () {

    $document = $this->createMock(Document\Page::class);
    $document->method('isPublished')->willReturn(true);
    $document->method('getProperty')->with('language')->willReturn('de');

    $context = $this->createMock(OpenDxpContextResolver::class);
    $context->method('matchesOpenDxpContext')->willReturn(true);

    $requestHelper = $this->createMock(RequestHelper::class);
    $requestHelper->method('isFrontendRequestByAdmin')->willReturn(false);

    $request = Request::create('/de/product/it');
    $requests = new RequestStack();
    $requests->push($request);

    $resolver = new DocumentResolver($requests);
    $resolver->setDocument($request, $document);

    // Symfony's own locale listener has run by now and taken the locale from the route.
    $request->setLocale('it');

    $listener = new ElementListener(
        $resolver,
        $this->createMock(EditmodeResolver::class),
        $requestHelper,
        $this->createMock(UserLoader::class),
    );
    $listener->setOpenDxpContextResolver($context);

    $listener->onKernelController(new ControllerEvent(
        $this->createMock(HttpKernelInterface::class),
        static fn () => new Response(),
        $request,
        HttpKernelInterface::MAIN_REQUEST,
    ));

    expect($resolver->getDocument($request))
        ->toBe($document)
        ->and($request->getLocale())
        ->toBe('it');
});
