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
    public function defaultAction(Request $request): Response
    {
        if ($request->attributes->get('test_doc_listing')) {
            (new Document\Listing())->getData();
        }

        if ($request->attributes->get('test_asset_listing')) {
            (new Asset\Listing())->getData();
        }

        $template = $request->attributes->get('_template');

        $response = $template !== null
            ? $this->render($template)
            : new Response('<html><body>test</body></html>');

        $response->setPublic();
        $response->setSharedMaxAge(3600);

        return $response;
    }

    public function fragmentAction(int $docId): Response
    {
        Document::getById($docId, ['force' => true]);

        return new Response('fragment-ok');
    }
}
