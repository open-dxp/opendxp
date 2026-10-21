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

use OpenDxp\Model\Document;
use OpenDxp\Model\Tool\Email\Log;

/**
 * @extends AbstractSavingFactory<Log>
 */
final class EmailLogFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return Log::class;
    }

    public function forDocument(Document $document): static
    {
        return $this->with(['documentId' => $document->getId()]);
    }

    public function sentAt(int $timestamp): static
    {
        return $this->with(['sentDate' => $timestamp]);
    }

    protected function defaults(): array
    {
        return [
            'from' => self::faker()->safeEmail(),
            'to' => self::faker()->safeEmail(),
            'subject' => self::faker()->sentence(),
            'sentDate' => self::faker()->unixTime(),
            'params' => [],
        ];
    }
}
