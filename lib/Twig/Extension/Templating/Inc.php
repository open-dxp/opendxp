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

namespace OpenDxp\Twig\Extension\Templating;

use OpenDxp\Http\Request\Resolver\EditmodeResolver;
use OpenDxp\Model\Document\PageSnippet;
use OpenDxp\Templating\Renderer\IncludeRenderer;
use OpenDxp\Twig\Extension\Templating\Traits\HelperCharsetTrait;
use Twig\Extension\RuntimeExtensionInterface;

class Inc implements RuntimeExtensionInterface
{
    use HelperCharsetTrait;

    public function __construct(protected IncludeRenderer $includeRenderer, protected EditmodeResolver $editmodeResolver)
    {
    }

    public function __invoke(int|string|PageSnippet $include, array $params = [], bool $cacheEnabled = true, ?bool $editmode = null): string
    {
        if (null === $editmode) {
            $editmode = $this->editmodeResolver->isEditmode();
        }

        return $this->includeRenderer->render($include, $params, $editmode, $cacheEnabled);
    }
}
