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

use OpenDxp\Mail;
use Symfony\Component\Mime\Header\Headers;

function mailHeaders(string $from, string $to): Headers
{
    return (new Headers())
        ->addMailboxListHeader('From', [$from])
        ->addMailboxListHeader('To', [$to]);
}

/**
 * @param array<string, mixed> $params
 */
function renderedMailBody(string $html, array $params = []): string
{
    $mail = new Mail();
    $mail->html($html);
    $mail->setParams($params);

    return $mail->getBodyHtmlRendered();
}
