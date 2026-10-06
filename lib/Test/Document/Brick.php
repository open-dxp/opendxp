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

namespace OpenDxp\Test\Document;

use OpenDxp\Model\Document\Editable;

/**
 * A brick is one instance of an areabrick on a document, with the values of its editables.
 */
interface Brick
{
    /**
     * The id the areabrick is registered with.
     */
    public function id(): string;

    /**
     * The editables of the brick, by the name the areabrick gives them in its template.
     *
     * @return array<string, Editable>
     */
    public function editables(): array;
}
