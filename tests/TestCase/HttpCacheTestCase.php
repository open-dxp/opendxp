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


namespace OpenDxp\Tests\TestCase;

use OpenDxp\HttpCache\HttpCacheTagCollectorInterface;
use OpenDxp\Model\Document\Page;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\TestFoundation\Container;
use OpenDxp\TestFoundation\StateTestCase;
use OpenDxp\Tests\Application\Controller\TagCollectionController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

abstract class HttpCacheTestCase extends StateTestCase
{
    protected static function state(): string
    {
        return 'http_cache';
    }

    protected function taggedPage(): Page
    {
        return DocumentPageFactory::new()
            ->withController(TagCollectionController::class, 'defaultAction')
            ->create();
    }

    protected function tagCollector(): HttpCacheTagCollectorInterface
    {
        return Container::get(HttpCacheTagCollectorInterface::class);
    }

    protected function tagsOf(Request $request): string
    {
        $response = Container::get(HttpKernelInterface::class)->handle($request);

        return $response->headers->get('X-Cache-Tags', '');
    }
}
