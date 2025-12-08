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

namespace OpenDxp\Twig\Extension;

use OpenDxp\Model\Document;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

/**
 * @internal
 */
class DocumentHelperExtensions extends AbstractExtension
{
    #[Override]
    public function getTests(): array
    {
        return [
            new TwigTest('opendxp_document', static fn ($object) => $object instanceof Document),
            new TwigTest('opendxp_document_email', static fn ($object) => $object instanceof Document\Email),
            new TwigTest('opendxp_document_folder', static fn ($object) => $object instanceof Document\Folder),
            new TwigTest('opendxp_document_hardlink', static fn ($object) => $object instanceof Document\Hardlink),
            new TwigTest('opendxp_document_page', static fn ($object) => $object instanceof Document\Page),
            new TwigTest('opendxp_document_link', static fn ($object) => $object instanceof Document\Link),
            new TwigTest('opendxp_document_page_snippet', static fn ($object) => $object instanceof Document\PageSnippet),
            new TwigTest('opendxp_document_snippet', static fn ($object) => $object instanceof Document\Snippet),
        ];
    }
}
