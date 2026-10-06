<?php

declare(strict_types=1);

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
