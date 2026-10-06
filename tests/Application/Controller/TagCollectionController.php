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

namespace OpenDxp\Tests\Application\Controller;

use OpenDxp\Controller\FrontendController;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Document;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TagCollectionController extends FrontendController
{
    public function defaultAction(): Response
    {
        return $this->cacheable(new Response('<html><body>test</body></html>'));
    }

    public function templateAction(Request $request): Response
    {
        $template = $request->attributes->get('_template');

        return $this->cacheable($this->render($template));
    }

    public function documentListingAction(): Response
    {
        (new Document\Listing())->getData();

        return $this->defaultAction();
    }

    public function assetListingAction(): Response
    {
        (new Asset\Listing())->getData();

        return $this->defaultAction();
    }

    public function fragmentAction(int $documentId): Response
    {
        Document::getById($documentId, ['force' => true]);

        return new Response('fragment');
    }

    private function cacheable(Response $response): Response
    {
        $response->setPublic();
        $response->setSharedMaxAge(3600);

        return $response;
    }
}
