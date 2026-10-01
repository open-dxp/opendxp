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

namespace OpenDxp\Tests\Support\Controller;

use OpenDxp\Model\Asset;
use OpenDxp\Model\Document;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * @deprecated since OpenDXP 1.5 and will be removed in 2.0
 */
class DefaultController
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function defaultAction(Request $request): Response
    {
        $template = $request->attributes->get('_template');

        if ($request->attributes->get('test_doc_listing')) {
            (new Document\Listing())->getData();
        }

        if ($request->attributes->get('test_asset_listing')) {
            (new Asset\Listing())->getData();
        }

        $content = $template !== null
            ? $this->twig->render($template)
            : '<html><body>test</body></html>';

        $response = new Response($content);
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
