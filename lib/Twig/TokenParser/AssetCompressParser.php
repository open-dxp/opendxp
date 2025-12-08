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

use OpenDxp\Twig\Node\AssetCompressNode;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * @internal
 *
 * The spaceless tag only removes spaces between HTML elements. This removes all newlines in a block and is suited
 * for a simple minification of CSS/JS assets.
 */
class AssetCompressParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse($this->decideAssetCompressEnd(...), true);
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new AssetCompressNode($body, $lineno, $this->getTag());
    }

    public function decideAssetCompressEnd(Token $token): bool
    {
        return $token->test('endopendxpassetcompress');
    }

    public function getTag(): string
    {
        return 'opendxpassetcompress';
    }
}
