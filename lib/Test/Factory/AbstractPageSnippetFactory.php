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

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Document\PageSnippet;

/**
 * @template T of PageSnippet
 *
 * @extends AbstractDocumentFactory<T>
 */
abstract class AbstractPageSnippetFactory extends AbstractDocumentFactory
{
    /**
     * @param class-string $controller
     */
    public function withController(string $controller, string $action): static
    {
        return $this->with(['controller' => sprintf('%s::%s', $controller, $action)]);
    }

    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'missingRequiredEditable' => false,
        ];
    }

    protected function initialize(): static
    {
        return parent::initialize()->withNavigationName();
    }
}
