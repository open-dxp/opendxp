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

namespace OpenDxp\Mail\Plugins;

use Exception;
use OpenDxp\Helper\Mail as MailHelper;
use OpenDxp\Mail;
use OpenDxp\SystemSettingsConfig;
use Symfony\Component\Mime\Header\MailboxListHeader;

/**
 * @internal
 */
final class RedirectingPlugin
{
    /**
     * The recipient who will receive all messages.
     *
     */
    private array $recipient;

    /**
     * Create a new RedirectingPlugin.
     *
     */
    public function __construct(array $recipient = [])
    {
        $config = SystemSettingsConfig::get()['email'];
        if (!empty($config['debug']['email_addresses'])) {
            $recipient = [...$recipient, ...array_filter(explode(',', $config['debug']['email_addresses']))];
        }

        $this->recipient = $recipient;
    }

    /**
     * Set the recipient of all messages.
     *
     */
    public function setRecipient(array $recipient): void
    {
        $this->recipient = $recipient;
    }

    /**
     * Get the recipient of all messages.
     *
     */
    public function getRecipient(): array
    {
        return $this->recipient;
    }

    /**
     * Invoked immediately before the Message is sent.
     *
     *
     */
    public function beforeSendPerformed(Mail $message): void
    {
        // additional checks if message is OpenDxp\Mail
        if ($message->doRedirectMailsToDebugMailAddresses()) {
            if (empty($this->getRecipient())) {
                throw new Exception('No valid debug email address given in "Settings" -> "System Settings" -> "Debug"');
            }

            $this->appendDebugInformation($message);

            // Set headers first to get actual data
            $headers = $message->getHeaders();
            $headers->add(new MailboxListHeader('X-OpenDxp-Debug-To', $message->getTo()));
            $headers->add(new MailboxListHeader('X-OpenDxp-Debug-Cc', $message->getCc()));
            $headers->add(new MailboxListHeader('X-OpenDxp-Debug-Bcc', $message->getBcc()));
            $headers->add(new MailboxListHeader('X-OpenDxp-Debug-ReplyTo', $message->getReplyTo()));

            // Clear all recipients before setting debug recipients
            $message->clearRecipients();

            // Add debug recipients as recipients
            foreach ($this->recipient as $recipient) {
                $message->addTo($recipient);
            }
        }
    }

    /**
     * Invoked immediately after the Message is sent.
     */
    public function sendPerformed(Mail $message): void
    {
        if ($message->doRedirectMailsToDebugMailAddresses()) {
            $this->setSenderAndReceiversParams($message);
            $this->removeDebugInformation($message);
        }
    }

    /**
     * Appends debug information to message
     */
    private function appendDebugInformation(Mail $message): void
    {
        if (!$message->isPreventingDebugInformationAppending()) {
            $originalData = [];

            //adding the debug information to the html email
            $html = $message->getHtmlBody();
            $text = $message->getTextBody();
            if (!empty($html)) {
                $originalData['html'] = $html;

                $debugInformation = MailHelper::getDebugInformation('html', $message);
                $debugInformationStyling = MailHelper::getDebugInformationCssStyle();

                $html = preg_replace("!(</\s*body\s*>)!is", "$debugInformation\\1", $html);
                $html = preg_replace("!(<\s*head\s*>)!is", "\\1$debugInformationStyling", $html);

                $message->html($html);
            } elseif (!empty($text)) {
                $originalData['text'] = $text;

                $rawText = $text;
                $debugInformation = MailHelper::getDebugInformation('text', $message);
                $rawText .= $debugInformation;
                $message->text($rawText);
            }

            //setting debug subject
            $subject = $message->getSubject();

            $originalData['subject'] = $subject;
            $message->subject('Debug email: ' . $subject);

            // Set receiver & sender data.
            $originalData['From'] = $message->getFrom();
            $originalData['To'] = $message->getTo();
            $originalData['Cc'] = $message->getCc();
            $originalData['Bcc'] = $message->getBcc();
            $originalData['ReplyTo'] = $message->getReplyTo();

            $message->setOriginalData($originalData);
        }
    }

    /**
     * Sets the sender and receiver information of the mail to keep the log searchable for the original data.
     */
    private function setSenderAndReceiversParams(Mail $message): void
    {
        $originalData = $message->getOriginalData();

        $message->setParam('Debug-Redirected', 'true');
        foreach (['From', 'To', 'Cc', 'Bcc', 'ReplyTo'] as $k) {
            // Add parameters to show this was redirected
            $message->setParam('Debug-Original-' . $k, MailHelper::formatDebugReceivers($originalData[$k]));
        }
    }

    /**
     * removes debug information from message and resets it
     */
    private function removeDebugInformation(Mail $message): void
    {
        $originalData = $message->getOriginalData();

        if (isset($originalData['html']) && $originalData['html']) {
            $message->html($originalData['html']);
        }
        if (isset($originalData['text']) && $originalData['text']) {
            $message->text($originalData['text']);
        }
        if (isset($originalData['subject']) && $originalData['subject']) {
            $message->subject($originalData['subject']);
        }

        $message->setOriginalData(null);
    }
}
