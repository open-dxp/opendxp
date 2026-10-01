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
use OpenDxp\Tests\Support\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ElementListenerTest extends TestCase
{
    public function testDocumentDoesNotChangeLocaleOnKernelController(): void
    {
        $document = $this->createMock(Document\Page::class);
        $document->method('isPublished')->willReturn(true);
        $document->method('getProperty')->with('language')->willReturn('de');

        $contextResolver = $this->createMock(OpenDxpContextResolver::class);
        $contextResolver->method('matchesOpenDxpContext')->willReturn(true);

        $requestHelper = $this->createMock(RequestHelper::class);
        $requestHelper->method('isFrontendRequestByAdmin')->willReturn(false);

        $request = Request::create('/de/product/it');

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $documentResolver = new DocumentResolver($requestStack);
        $documentResolver->setDocument($request, $document);

        // what Symfony's LocaleListener does for a route with _locale = it
        $request->setLocale('it');

        $listener = new ElementListener(
            $documentResolver,
            $this->createMock(EditmodeResolver::class),
            $requestHelper,
            $this->createMock(UserLoader::class)
        );
        $listener->setOpenDxpContextResolver($contextResolver);

        $listener->onKernelController(new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            static fn () => new Response(),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        ));

        $this->assertSame($document, $documentResolver->getDocument($request));
        $this->assertSame('it', $request->getLocale());
    }
}
