<?php

declare(strict_types=1);

/**
 * This file is part of the Webware Mailer package.
 *
 * Copyright (c) 2025-2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webware\Mailer\Adapter;

use Override;
use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer as BaseMailer;
use SensitiveParameter;

/**
 * PHPMailer adapter.
 *
 * Transport settings are supplied by the factory and are read-only here; this
 * adapter never sees a message until one is being sent. PHPMailer keeps transport
 * and message state in a single object, so that object is built once and kept,
 * and each send clears the message state before applying its own. Transport
 * settings (including `smtpKeepAlive`) survive from one send to the next, which
 * is why the adapter can safely be a shared service.
 *
 * `enableExceptions`, `useSmtp`, `isMail()` and `isSmtp()` are PHPMailer-specific
 * and deliberately live here rather than on the shared contract.
 *
 * @type PhpMailerConfig = array{
 *   enableExceptions?: bool,
 *   useSmtp?: bool,
 *   host?: non-empty-string,
 *   port?: int<1, 65535>,
 *   smtpAuth?: bool,
 *   smtpKeepAlive?: bool,
 *   username?: string,
 *   password?: string,
 *   smtpSecure?: ''|'tls'|'ssl',
 *   timeout?: positive-int,
 * }
 */
final class PhpMailer implements AdapterInterface
{
    /**
     * The shared transport. PHPMailer holds both transport and message state on
     * one object, so the same instance is reused and its message state is reset
     * per send.
     */
    private readonly BaseMailer $mailer;

    /**
     * PHPMailer's own charset and encoding defaults, captured from a pristine
     * instance so a reset restores the library's values rather than a value this
     * package invented.
     */
    private readonly string $defaultCharset;

    private readonly string $defaultEncoding;

    /**
     * @throws MailerException
     */
    private function __construct(
        public private(set) bool $enableExceptions,
        public private(set) string $host,
        #[SensitiveParameter]
        public private(set) string $password,
        public private(set) int $port,
        public private(set) bool $smtpAuth,
        public private(set) bool $smtpKeepAlive,
        public private(set) string $smtpSecure,
        public private(set) int $timeout,
        public private(set) string $username,
        public private(set) bool $useSmtp,
    ) {
        $this->mailer          = $this->createTransport();
        $this->defaultCharset  = $this->mailer->CharSet;
        $this->defaultEncoding = $this->mailer->Encoding;
    }

    /**
     * Builds an adapter from this implementation's own configuration shape.
     *
     * @param PhpMailerConfig $config
     *
     * @throws MailerException
     */
    public static function fromConfig(array $config = []): self
    {
        return new self(
            enableExceptions: $config['enableExceptions'] ?? true,
            host            : $config['host'] ?? 'localhost',
            password        : $config['password'] ?? '',
            port            : $config['port'] ?? 25,
            smtpAuth        : $config['smtpAuth'] ?? false,
            smtpKeepAlive   : $config['smtpKeepAlive'] ?? false,
            smtpSecure      : $config['smtpSecure'] ?? '',
            timeout         : $config['timeout'] ?? 300,
            username        : $config['username'] ?? '',
            useSmtp         : $config['useSmtp'] ?? false,
        );
    }

    public function isMail(): bool
    {
        return ! $this->useSmtp;
    }

    public function isSmtp(): bool
    {
        return $this->useSmtp;
    }

    /**
     * The transport is built once, when the adapter is created, and reused for
     * every send. Message state is cleared and reapplied per send so nothing
     * carries over; `smtpKeepAlive` decides whether PHPMailer keeps the SMTP
     * connection open between sends.
     *
     * @throws MailerException
     */
    #[Override]
    public function send(MessageInterface $message): bool
    {
        $this->resetMessageState();
        $this->applyMessage($message);

        return $this->mailer->send();
    }

    /**
     * @throws MailerException
     */
    private function applyMessage(MessageInterface $message): void
    {
        // An empty value leaves PHPMailer's own default in force rather than a
        // value this package invented.
        if ('' !== $message->charset) {
            $this->mailer->CharSet = $message->charset;
        }

        if ('' !== $message->encoding) {
            $this->mailer->Encoding = $message->encoding;
        }

        if ('' !== $message->from) {
            $this->mailer->setFrom($message->from, $message->fromName);
        }

        foreach ($message->to as [$email, $name]) {
            $this->mailer->addAddress($email, $name);
        }

        foreach ($message->cc as [$email, $name]) {
            $this->mailer->addCC($email, $name);
        }

        foreach ($message->bcc as [$email, $name]) {
            $this->mailer->addBCC($email, $name);
        }

        foreach ($message->replyTo as [$email, $name]) {
            $this->mailer->addReplyTo($email, $name);
        }

        foreach ($message->headers as [$name, $value]) {
            $this->mailer->addCustomHeader($name, $value);
        }

        foreach ($message->attachments as [$content, $name, $mimeType, $isRawContent]) {
            if ($isRawContent) {
                $this->mailer->addStringAttachment($content, $name, encoding: 'base64', type: $mimeType);

                continue;
            }

            $this->mailer->addAttachment($content, $name, encoding: 'base64', type: $mimeType);
        }

        $this->mailer->Subject = $message->subject;
        $this->mailer->Body    = $message->body;
        $this->mailer->AltBody = $message->altBody;

        $this->mailer->isHTML($message->html);
    }

    /**
     * @throws MailerException
     */
    private function createTransport(): BaseMailer
    {
        $mailer = new BaseMailer($this->enableExceptions);

        if ($this->useSmtp) {
            $mailer->isSMTP();
        }

        $mailer->Host          = $this->host;
        $mailer->Port          = $this->port;
        $mailer->SMTPAuth      = $this->smtpAuth;
        $mailer->Username      = $this->username;
        $mailer->Password      = $this->password;
        $mailer->SMTPSecure    = $this->smtpSecure;
        $mailer->SMTPKeepAlive = $this->smtpKeepAlive;
        $mailer->Timeout       = $this->timeout;

        return $mailer;
    }

    /**
     * Returns the shared transport to a pristine message state. Transport settings
     * are left alone: only what a message owns is cleared.
     */
    private function resetMessageState(): void
    {
        $this->mailer->clearAllRecipients();
        $this->mailer->clearAttachments();
        $this->mailer->clearCustomHeaders();

        $this->mailer->AltBody  = '';
        $this->mailer->Body     = '';
        $this->mailer->CharSet  = $this->defaultCharset;
        $this->mailer->Encoding = $this->defaultEncoding;
        $this->mailer->From     = '';
        $this->mailer->FromName = '';
        $this->mailer->Subject  = '';
    }
}
