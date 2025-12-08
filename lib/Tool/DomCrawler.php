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

namespace OpenDxp\Tool;

use Override;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
class DomCrawler extends Crawler
{
    public const string FRAGMENT_WRAPPER_TAG = 'opendxp-fragment-wrapper';

    private bool $wrappedHtmlFragment = false;

    public function __construct($node = null, ?string $uri = null, ?string $baseHref = null)
    {
        // check if given node is an HTML fragment, if so wrap it in a custom tag, otherwise
        // DomDocument wraps standalone text-nodes (without a parent node) into <p> tags
        if (is_string($node) && !preg_match('@</(body|html)>@i', $node)) {
            $node = sprintf('<!doctype html><html><%s>%s</%s></html>', self::FRAGMENT_WRAPPER_TAG, $node, self::FRAGMENT_WRAPPER_TAG);
            $this->wrappedHtmlFragment = true;
        }

        parent::__construct($node, $uri, $baseHref);
    }

    #[Override]
    public function html(?string $default = null): string
    {
        if ($this->wrappedHtmlFragment) {
            return $this->filter(self::FRAGMENT_WRAPPER_TAG)->html();
        }

        return parent::html($default);
    }
}
