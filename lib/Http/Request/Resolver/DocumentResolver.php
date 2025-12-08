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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Http\Request\Resolver;

use OpenDxp\Model\Document;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpFoundation\Request;

class DocumentResolver extends AbstractRequestResolver
{
    public function getDocument(?Request $request = null): ?Document
    {
        if (!$request instanceof \Symfony\Component\HttpFoundation\Request) {
            $request = $this->getCurrentRequest();
        }

        $content = $request->attributes->get(DynamicRouter::CONTENT_KEY);
        if ($content instanceof Document) {
            return $content;
        }

        return null;
    }

    public function setDocument(Request $request, Document $document): void
    {
        $request->attributes->set(DynamicRouter::CONTENT_KEY, $document);
        if ($document->getProperty('language')) {
            $request->setLocale($document->getProperty('language'));
        }
    }
}
