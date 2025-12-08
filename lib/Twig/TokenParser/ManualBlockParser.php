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

namespace OpenDxp\Twig\TokenParser;

use OpenDxp\Twig\Node\ManualBlockNode;
use OpenDxp\Twig\Options\HasBlockOptionsTrait;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * @internal
 */
final class ManualBlockParser extends AbstractTokenParser
{
    use HasBlockOptionsTrait;

    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();

        $stream = $this->parser->getStream();
        $blockName = $stream->expect(Token::STRING_TYPE, null, 'Please specify a block name')->getValue();

        $options = $this->getBlockOptions($stream, $this->parser);
        $options->setManual(true);

        $stream->expect(Token::BLOCK_END_TYPE);

        $startNode = $this->parser->subparse($this->decideIterateStart(...), true);
        $stream->expect(Token::BLOCK_END_TYPE);

        $bodyNode = $this->parser->subparse($this->decideIterateEnd(...), true);
        $stream->expect(Token::BLOCK_END_TYPE);

        $endNode = $this->parser->subparse($this->decideOpenDxpManualBlockEnd(...), true);

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new ManualBlockNode(
            $blockName,
            $options,
            $startNode,
            $bodyNode,
            $endNode,
            $lineno,
            $this->getTag()
        );
    }

    public function decideIterateStart(Token $token): bool
    {
        return $token->test('blockiterate');
    }

    public function decideIterateEnd(Token $token): bool
    {
        return $token->test('endblockiterate');
    }

    public function decideOpenDxpManualBlockEnd(Token $token): bool
    {
        return $token->test('endopendxpmanualblock');
    }

    public function getTag()
    {
        return 'opendxpmanualblock';
    }
}
