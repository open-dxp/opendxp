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

namespace OpenDxp\Helper\SymfonyExpression;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;

/**
 * @internal
 */
final class ExpressionService implements ExpressionServiceInterface
{
    public function evaluate(
        string $condition,
        array $contentVariables
    ): bool {
        $expressionLanguage = new ExpressionLanguage();
        //overwrite constant function to avoid exposing internal information
        $expressionLanguage->register('constant', function (): void {
            throw new SyntaxError('`constant` function not available');
        }, function (): void {
            throw new SyntaxError('`constant` function not available');
        });

        return (bool)$expressionLanguage->evaluate($condition, $contentVariables);
    }
}
