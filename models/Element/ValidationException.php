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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Model\Element;

use Exception;
use Psr\Log\LogLevel;
use Symfony\Component\HttpKernel\Attribute\WithLogLevel;

/**
 * Signals that an element failed validation, such as a mandatory field left empty.
 * The admin's AdminExceptionListener turns it into a 422 response that shows the message to the user.
 * Symfony's ErrorListener logs every exception, this one at warning level, not info, since outside the admin an uncaught one still ends in a 500 response.
 */
#[WithLogLevel(LogLevel::WARNING)]
class ValidationException extends Exception
{
    protected array $contextStack = [];

    /** @var Exception[] */
    protected array $subItems = [];

    /**
     * @return Exception[]
     */
    public function getSubItems(): array
    {
        return $this->subItems;
    }

    /**
     * @param Exception[] $subItems
     */
    public function setSubItems(array $subItems = []): void
    {
        $this->subItems = $subItems;
    }

    public function addContext(string $context): void
    {
        $this->contextStack[] = $context;
    }

    public function getContextStack(): array
    {
        return $this->contextStack;
    }

    public function __toString(): string
    {
        $result = parent::__toString();
        foreach ($this->subItems as $subItem) {
            $result .= "\n\n";
            $result .= $subItem->__toString();
        }

        return $result;
    }

    public function getAggregatedMessage(): string
    {
        $msg = $this->getMessage();
        $contextStack = $this->getContextStack();
        if ($contextStack) {
            $msg .= '[ '.$contextStack[0].' ]';
        }

        $subItems = $this->getSubItems();
        if (count($subItems) > 0) {
            $msg .= ' (';
            $subItemParts = [];

            foreach ($subItems as $subItem) {
                if ($subItem instanceof self) {
                    $subItemMessage = $subItem->getAggregatedMessage();
                    $contextStack = $subItem->getContextStack();
                    if ($contextStack) {
                        $subItemMessage .= '[ '.$contextStack[0].' ]';
                    }
                } else {
                    $subItemMessage = $subItem->getMessage();
                }
                $subItemParts[] = $subItemMessage;
            }
            $msg .= implode(', ', $subItemParts);
            $msg .= ')';
        }

        return $msg;
    }
}
