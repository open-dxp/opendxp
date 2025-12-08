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

namespace OpenDxp\Http\Response;

use InvalidArgumentException;
use OpenDxp\Http\ResponseHelper;
use OpenDxp\Tool\DomCrawler;
use Symfony\Component\HttpFoundation\Response;

class CodeInjector
{
    public const SELECTOR_BODY = 'body';

    public const SELECTOR_HEAD = 'head';

    public const POSITION_BEGINNING = 'beginning';

    public const POSITION_END = 'end';

    public const REPLACE = 'replace';

    private static array $presetSelectors = [
        self::SELECTOR_HEAD,
        self::SELECTOR_BODY,
    ];

    private static array $validPositions = [
        self::POSITION_BEGINNING,
        self::POSITION_END,
        self::REPLACE,
    ];

    public function __construct(private readonly ResponseHelper $responseHelper)
    {
    }

    public function inject(Response $response, string $code, string $selector = self::SELECTOR_BODY, string $position = self::POSITION_END): void
    {
        if (empty($code)) {
            return;
        }

        if (!$this->responseHelper->isHtmlResponse($response)) {
            return;
        }

        $content = $response->getContent();
        $result = $this->injectIntoHtml($content, $code, $selector, $position, $response->getCharset());

        $response->setContent($result);
    }

    /**
     * @internal
     *
     *
     */
    public function injectIntoHtml(string $html, string $code, string $selector, string $position, string $charset = 'UTF-8'): string
    {
        if (!in_array($position, self::$validPositions)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid position. Supported positions are: %s',
                implode(', ', self::$validPositions)
            ));
        }

        if (in_array($selector, self::$presetSelectors, true)) {
            return $this->injectIntoPresetSelector($html, $code, $selector, $position);
        }
        return $this->injectIntoDomSelector($html, $code, $selector, $position);
    }

    private function injectIntoPresetSelector(string $html, string $code, string $selector, string $position): string
    {
        $startTagPattern = '<\s*?' . $selector . '\b[^>]*>';
        $endTagPattern = '</' . $selector . '\b[^>]*>';

        // temporary placeholder to use in preg_replace as we can't be sure the code breaks our replacement
        $injectTpl = sprintf('----%s----', uniqid('INJECT:', true));

        if (self::REPLACE === $position) {
            $html = preg_replace(
                '#(' . $startTagPattern . ')(.*?)(' . $endTagPattern . ')#s',
                '${1}' . $injectTpl . '${3}',
                $html
            );
        } elseif (self::POSITION_BEGINNING === $position) {
            $html = preg_replace(
                '#(' . $startTagPattern . ')#s',
                '${1}' . $injectTpl,
                $html
            );
        } elseif (self::POSITION_END === $position) {
            $html = preg_replace(
                '#(' . $endTagPattern . ')#s',
                $injectTpl . '${1}',
                $html
            );
        }

        // replace placeholder with actual code
        $html = str_replace($injectTpl, $code, $html);

        return $html;
    }

    private function injectIntoDomSelector(string $html, string $code, string $selector, string $position): string
    {
        $dom = new DomCrawler($html);
        $element = $dom->filter($selector)->eq(0);
        if ($element->count() && $node = $element->getNode(0)) {
            if (self::REPLACE === $position) {
                $node->textContent = $code;
            } elseif (self::POSITION_BEGINNING === $position) {
                $node->textContent = $code . $element->html();
            } elseif (self::POSITION_END === $position) {
                $node->textContent = $element->html() . $code;
            }
        }

        $html = html_entity_decode($dom->outerHtml());
        $dom->clear();
        unset($dom);

        return trim($html);
    }
}
