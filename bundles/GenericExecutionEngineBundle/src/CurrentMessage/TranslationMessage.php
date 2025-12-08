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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\CurrentMessage;

use JsonException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final readonly class TranslationMessage implements MessageInterface
{
    public function __construct(
        private string $key,
        private array $params,
        private string $domain,
        private TranslatorInterface $translator
    ) {
    }

    public function getSerializedString(): string
    {
        try {
            return json_encode($this->buildMessageArray(), JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return '';
        }
    }

    private function buildMessageArray(): array
    {
        return [
            'key' => $this->key,
            'params' => $this->params,
            'domain' => $this->domain,
        ];
    }

    public function getMessage(): string
    {
        return $this->translator->trans($this->key, $this->params, $this->domain);
    }
}
