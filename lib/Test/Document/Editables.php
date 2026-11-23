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
use OpenDxp\Model\Document\PageSnippet;

final class Editables
{
    /**
     * @param array<string, Editable> $editables
     */
    public static function copyInto(PageSnippet $document, array $editables): void
    {
        foreach ($editables as $name => $editable) {
            // Every document needs editables of its own.
            $copy = clone $editable;
            $copy->setName($name);
            $document->setEditable($copy);
        }
    }
}
