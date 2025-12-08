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

namespace OpenDxp\Controller\ArgumentValueResolver;

use OpenDxp\Http\Request\Resolver\DocumentResolver;
use OpenDxp\Model\Document;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Adds support for type hinting controller actions against `Document $document` and getting the current document.
 *
 * @internal
 */
final class DocumentValueResolver implements ValueResolverInterface
{
    public function __construct(protected DocumentResolver $documentResolver)
    {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== Document::class) {
            return [];
        }

        if ($argument->getName() !== 'document') {
            return [];
        }

        if ($document = $this->documentResolver->getDocument($request)) {
            return [$document];
        }

        return [];
    }
}
