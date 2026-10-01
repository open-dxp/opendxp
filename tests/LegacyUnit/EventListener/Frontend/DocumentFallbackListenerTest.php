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
use OpenDxp\Tests\Support\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class DocumentFallbackListenerTest extends TestCase
{
    public function testFallbackDocumentDoesNotChangeLocaleOnKernelController(): void
    {
        $document = $this->createMock(Document\Page::class);
        $document->method('getProperty')->with('language')->willReturn('de');

        $documentService = $this->createMock(Document\Service::class);
        $documentService->method('getNearestDocumentByPath')->willReturn($document);

        $contextResolver = $this->createMock(OpenDxpContextResolver::class);
        $contextResolver->method('matchesOpenDxpContext')->willReturn(true);

        $requestStack = new RequestStack();
        $documentResolver = new DocumentResolver($requestStack);

        $listener = new DocumentFallbackListener(
            $requestStack,
            $documentResolver,
            $this->createMock(SiteResolver::class),
            $documentService
        );

        $listener->setOpenDxpContextResolver($contextResolver);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/de/product/it');
        $requestStack->push($request);

        $listener->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        // what Symfony's LocaleListener does for a route with _locale = it
        $request->setLocale('it');

        $listener->onKernelController(new ControllerEvent(
            $kernel,
            static fn () => new Response(),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ));

        $this->assertSame($document, $documentResolver->getDocument($request));
        $this->assertSame('it', $request->getLocale());
    }
}
