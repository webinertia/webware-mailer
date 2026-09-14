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
 * The transport is built by the constructor from this instance's own state and is
 * never cloned or replaced, so it cannot be swapped at runtime. Every method
 * returns a new instance carrying that state forward.
 *
 * `enableExceptions`, `useSmtp`, `isMail()` and `isSmtp()` are PHPMailer-specific
 * and deliberately live here rather than on the shared contract.
 *
 * Configuration shape for this implementation. Every key is optional; the defaults
 * applied by `fromConfig()` are this adapter's own.
 *
 * @type PhpMailerConfig = array{
 *   enableExceptions?: bool,
 *   useSmtp?: bool,
 *   host?: non-empty-string,
 *   port?: int<1, 65535>,
 *   smtpAuth?: bool,
 *   username?: string,
 *   password?: string,
 *   smtpSecure?: ''|'tls'|'ssl',
 *   charset?: non-empty-string,
 *   encoding?: non-empty-string,
 *   timeout?: positive-int,
 *   from?: non-empty-string,
 *   fromName?: non-empty-string,
 * }
 */
final class PhpMailer implements AdapterInterface
{
    private BaseMailer $mailer;

    /**
     * @param list<array{0: string, 1: string, 2: string, 3: bool}> $attachments
     * @param list<array{0: string, 1: string}>                    $bcc
     * @param list<array{0: string, 1: string}>                    $cc
     * @param list<array{0: string, 1: string}>                    $headers
     * @param list<array{0: string, 1: string}>                    $replyTo
     * @param list<array{0: string, 1: string}>                    $to
     * @throws MailerException
     */
    private function __construct(
        public private(set) bool $enableExceptions,
        public private(set) string $charset,
        public private(set) string $encoding,
        public private(set) string $from,
        public private(set) string $fromName,
        public private(set) string $host,
        #[SensitiveParameter]
        public private(set) string $password,
        public private(set) int $port,
        public private(set) bool $smtpAuth,
        public private(set) string $smtpSecure,
        public private(set) int $timeout,
        public private(set) string $username,
        public private(set) bool $useSmtp,
        public private(set) string $altBody = '',
        public private(set) array $attachments = [],
        public private(set) array $bcc = [],
        public private(set) string $body = '',
        public private(set) array $cc = [],
        public private(set) array $headers = [],
        public private(set) bool $html = false,
        public private(set) array $replyTo = [],
        public private(set) string $subject = '',
        public private(set) array $to = [],
    ) {
        $this->mailer = new BaseMailer($enableExceptions);

        if ($this->useSmtp) {
            $this->mailer->isSMTP();
        }

        $this->mailer->Host       = $this->host;
        $this->mailer->Port       = $this->port;
        $this->mailer->SMTPAuth   = $this->smtpAuth;
        $this->mailer->Username   = $this->username;
        $this->mailer->Password   = $this->password;
        $this->mailer->SMTPSecure = $this->smtpSecure;
        $this->mailer->CharSet    = $this->charset;
        $this->mailer->Encoding   = $this->encoding;
        $this->mailer->Timeout    = $this->timeout;

        if ('' !== $this->from) {
            $this->mailer->setFrom($this->from, $this->fromName);
        }

        foreach ($this->to as [$email, $name]) {
            $this->mailer->addAddress($email, $name);
        }

        foreach ($this->cc as [$email, $name]) {
            $this->mailer->addCC($email, $name);
        }

        foreach ($this->bcc as [$email, $name]) {
            $this->mailer->addBCC($email, $name);
        }

        foreach ($this->replyTo as [$email, $name]) {
            $this->mailer->addReplyTo($email, $name);
        }

        foreach ($this->headers as [$name, $value]) {
            $this->mailer->addCustomHeader($name, $value);
        }

        foreach ($this->attachments as [$content, $name, $mimeType, $isRawContent]) {
            if ($isRawContent) {
                $this->mailer->addStringAttachment($content, $name, encoding: 'base64', type: $mimeType);

                continue;
            }

            $this->mailer->addAttachment($content, $name, encoding: 'base64', type: $mimeType);
        }

        $this->mailer->Subject = $this->subject;
        $this->mailer->Body    = $this->body;
        $this->mailer->AltBody = $this->altBody;

        $this->mailer->isHTML($this->html);
    }

    /**
     * Builds an adapter from this implementation's own configuration shape.
     *
     * @param PhpMailerConfig $config
     * @throws MailerException
     */
    public static function fromConfig(array $config = []): self
    {
        return new self(
            enableExceptions: $config['enableExceptions'] ?? true,
            useSmtp         : $config['useSmtp'] ?? false,
            host            : $config['host'] ?? 'localhost',
            port            : $config['port'] ?? 25,
            smtpAuth        : $config['smtpAuth'] ?? false,
            username        : $config['username'] ?? '',
            password        : $config['password'] ?? '',
            smtpSecure      : $config['smtpSecure'] ?? '',
            charset         : $config['charset'] ?? 'iso-8859-1',
            encoding        : $config['encoding'] ?? '8bit',
            timeout         : $config['timeout'] ?? 300,
            from            : $config['from'] ?? '',
            fromName        : $config['fromName'] ?? '',
        );
    }

    public function isMail(): bool
    {
        return 'mail' === $this->mailer->Mailer;
    }

    public function isSmtp(): bool
    {
        return 'smtp' === $this->mailer->Mailer;
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function send(): bool
    {
        return $this->mailer->send();
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withAltBody(string $altBody): static
    {
        return new static(
            enableExceptions: $this->enableExceptions,
            charset         : $this->charset,
            encoding        : $this->encoding,
            from            : $this->from,
            fromName        : $this->fromName,
            host            : $this->host,
            password        : $this->password,
            port            : $this->port,
            smtpAuth        : $this->smtpAuth,
            smtpSecure      : $this->smtpSecure,
            timeout         : $this->timeout,
            username        : $this->username,
            useSmtp         : $this->useSmtp,
            altBody         : $altBody,
            attachments     : $this->attachments,
            bcc             : $this->bcc,
            body            : $this->body,
            cc              : $this->cc,
            headers         : $this->headers,
            html            : $this->html,
            replyTo         : $this->replyTo,
            subject         : $this->subject,
            to              : $this->to,
        );
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withAttachment(string $path, string $name = '', string $mimeType = ''): static
    {
        return new static(
            enableExceptions: $this->enableExceptions,
            charset         : $this->charset,
            encoding        : $this->encoding,
            from            : $this->from,
            fromName        : $this->fromName,
            host            : $this->host,
            password        : $this->password,
            port            : $this->port,
            smtpAuth        : $this->smtpAuth,
            smtpSecure      : $this->smtpSecure,
            timeout         : $this->timeout,
            username        : $this->username,
            useSmtp         : $this->useSmtp,
            altBody         : $this->altBody,
            attachments     : [...$this->attachments, [$path, $name, $mimeType, false]],
            bcc             : $this->bcc,
            body            : $this->body,
            cc              : $this->cc,
            headers         : $this->headers,
            html            : $this->html,
            replyTo         : $this->replyTo,
            subject         : $this->subject,
            to              : $this->to,
        );
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withAttachmentFromString(string $content, string $name, string $mimeType = ''): static
    {
        return new static(
            enableExceptions: $this->enableExceptions,
            charset         : $this->charset,
            encoding        : $this->encoding,
            from            : $this->from,
            fromName        : $this->fromName,
            host            : $this->host,
            password        : $this->password,
            port            : $this->port,
            smtpAuth        : $this->smtpAuth,
            smtpSecure      : $this->smtpSecure,
            timeout         : $this->timeout,
            username        : $this->username,
            useSmtp         : $this->useSmtp,
            altBody         : $this->altBody,
            attachments     : [...$this->attachments, [$content, $name, $mimeType, true]],
            bcc             : $this->bcc,
            body            : $this->body,
            cc              : $this->cc,
            headers         : $this->headers,
            html            : $this->html,
            replyTo         : $this->replyTo,
            subject         : $this->subject,
            to              : $this->to,
        );
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withBcc(string $email, string $name = ''): static
    {
        return new static(
            enableExceptions: $this->enableExceptions,
            charset         : $this->charset,
            encoding        : $this->encoding,
            from            : $this->from,
            fromName        : $this->fromName,
            host            : $this->host,
            password        : $this->password,
            port            : $this->port,
            smtpAuth        : $this->smtpAuth,
            smtpSecure      : $this->smtpSecure,
            timeout         : $this->timeout,
            username        : $this->username,
            useSmtp         : $this->useSmtp,
            altBody         : $this->altBody,
            attachments     : $this->attachments,
            bcc             : [...$this->bcc, [$email, $name]],
            body            : $this->body,
            cc              : $this->cc,
            headers         : $this->headers,
            html            : $this->html,
            replyTo         : $this->replyTo,
            subject         : $this->subject,
            to              : $this->to,
        );
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withBody(string $body): static
    {
        return new static(
            enableExceptions: $this->enableExceptions,
            charset         : $this->charset,
            encoding        : $this->encoding,
            from            : $this->from,
            fromName        : $this->fromName,
            host            : $this->host,
            password        : $this->password,
            port            : $this->port,
            smtpAuth        : $this->smtpAuth,
            smtpSecure      : $this->smtpSecure,
            timeout         : $this->timeout,
            username        : $this->username,
            useSmtp         : $this->useSmtp,
            altBody         : $this->altBody,
            attachments     : $this->attachments,
            bcc             : $this->bcc,
            body            : $body,
            cc              : $this->cc,
            headers         : $this->headers,
            html            : $this->html,
            replyTo         : $this->replyTo,
            subject         : $this->subject,
            to              : $this->to,
        );
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withCc(string $email, string $name = ''): static
    {
        return new static(
            enableExceptions: $this->enableExceptions,
            charset         : $this->charset,
            encoding        : $this->encoding,
            from            : $this->from,
            fromName        : $this->fromName,
            host            : $this->host,
            password        : $this->password,
            port            : $this->port,
            smtpAuth        : $this->smtpAuth,
            smtpSecure      : $this->smtpSecure,
            timeout         : $this->timeout,
            username        : $this->username,
            useSmtp         : $this->useSmtp,
            altBody         : $this->altBody,
            attachments     : $this->attachments,
            bcc             : $this->bcc,
            body            : $this->body,
            cc              : [...$this->cc, [$email, $name]],
            headers         : $this->headers,
            html            : $this->html,
            replyTo         : $this->replyTo,
            subject         : $this->subject,
            to              : $this->to,
        );
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withCharset(string $charset): static
    {
        return new static(
            enableExceptions: $this->enableExceptions,
            charset         : $charset,
            encoding        : $this->encoding,
            from            : $this->from,
            fromName        : $this->fromName,
            host            : $this->host,
            password        : $this->password,
            port            : $this->port,
            smtpAuth        : $this->smtpAuth,
            smtpSecure      : $this->smtpSecure,
            timeout         : $this->timeout,
            username        : $this->username,
            useSmtp         : $this->useSmtp,
            altBody         : $this->altBody,
            attachments     : $this->attachments,
            bcc             : $this->bcc,
            body            : $this->body,
            cc              : $this->cc,
            headers         : $this->headers,
            html            : $this->html,
            replyTo         : $this->replyTo,
            subject         : $this->subject,
            to              : $this->to,
        );
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withEncoding(string $encoding): static
    {
        return new static(
            enableExceptions: $this->enableExceptions,
            charset         : $this->charset,
            encoding        : $encoding,
            from            : $this->from,
            fromName        : $this->fromName,
            host            : $this->host,
            password        : $this->password,
            port            : $this->port,
            smtpAuth        : $this->smtpAuth,
            smtpSecure      : $this->smtpSecure,
            timeout         : $this->timeout,
            username        : $this->username,
            useSmtp         : $this->useSmtp,
            altBody         : $this->altBody,
            attachments     : $this->attachments,
            bcc             : $this->bcc,
            body            : $this->body,
            cc              : $this->cc,
            headers         : $this->headers,
            html            : $this->html,
            replyTo         : $this->replyTo,
            subject         : $this->subject,
            to              : $this->to,
        );
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withFrom(string $email, string $name = ''): static
    {
        return new static(
            enableExceptions: $this->enableExceptions,
            charset         : $this->charset,
            encoding        : $this->encoding,
            from            : $email,
            fromName        : $name,
            host            : $this->host,
            password        : $this->password,
            port            : $this->port,
            smtpAuth        : $this->smtpAuth,
            smtpSecure      : $this->smtpSecure,
            timeout         : $this->timeout,
            username        : $this->username,
            useSmtp         : $this->useSmtp,
            altBody         : $this->altBody,
            attachments     : $this->attachments,
            bcc             : $this->bcc,
            body            : $this->body,
            cc              : $this->cc,
            headers         : $this->headers,
            html            : $this->html,
            replyTo         : $this->replyTo,
            subject         : $this->subject,
            to              : $this->to,
        );
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withHeader(string $name, string $value): static
    {
        return new static(
            enableExceptions: $this->enableExceptions,
            charset         : $this->charset,
            encoding        : $this->encoding,
            from            : $this->from,
            fromName        : $this->fromName,
            host            : $this->host,
            password        : $this->password,
            port            : $this->port,
            smtpAuth        : $this->smtpAuth,
            smtpSecure      : $this->smtpSecure,
            timeout         : $this->timeout,
            username        : $this->username,
            useSmtp         : $this->useSmtp,
            altBody         : $this->altBody,
            attachments     : $this->attachments,
            bcc             : $this->bcc,
            body            : $this->body,
            cc              : $this->cc,
            headers         : [...$this->headers, [$name, $value]],
            html            : $this->html,
            replyTo         : $this->replyTo,
            subject         : $this->subject,
            to              : $this->to,
        );
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withHtml(bool $flag = true): static
    {
        return new static(
            enableExceptions: $this->enableExceptions,
            charset         : $this->charset,
            encoding        : $this->encoding,
            from            : $this->from,
            fromName        : $this->fromName,
            host            : $this->host,
            password        : $this->password,
            port            : $this->port,
            smtpAuth        : $this->smtpAuth,
            smtpSecure      : $this->smtpSecure,
            timeout         : $this->timeout,
            username        : $this->username,
            useSmtp         : $this->useSmtp,
            altBody         : $this->altBody,
            attachments     : $this->attachments,
            bcc             : $this->bcc,
            body            : $this->body,
            cc              : $this->cc,
            headers         : $this->headers,
            html            : $flag,
            replyTo         : $this->replyTo,
            subject         : $this->subject,
            to              : $this->to,
        );
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withReplyTo(string $email, string $name = ''): static
    {
        return new static(
            enableExceptions: $this->enableExceptions,
            charset         : $this->charset,
            encoding        : $this->encoding,
            from            : $this->from,
            fromName        : $this->fromName,
            host            : $this->host,
            password        : $this->password,
            port            : $this->port,
            smtpAuth        : $this->smtpAuth,
            smtpSecure      : $this->smtpSecure,
            timeout         : $this->timeout,
            username        : $this->username,
            useSmtp         : $this->useSmtp,
            altBody         : $this->altBody,
            attachments     : $this->attachments,
            bcc             : $this->bcc,
            body            : $this->body,
            cc              : $this->cc,
            headers         : $this->headers,
            html            : $this->html,
            replyTo         : [...$this->replyTo, [$email, $name]],
            subject         : $this->subject,
            to              : $this->to,
        );
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withSubject(string $subject): static
    {
        return new static(
            enableExceptions: $this->enableExceptions,
            charset         : $this->charset,
            encoding        : $this->encoding,
            from            : $this->from,
            fromName        : $this->fromName,
            host            : $this->host,
            password        : $this->password,
            port            : $this->port,
            smtpAuth        : $this->smtpAuth,
            smtpSecure      : $this->smtpSecure,
            timeout         : $this->timeout,
            username        : $this->username,
            useSmtp         : $this->useSmtp,
            altBody         : $this->altBody,
            attachments     : $this->attachments,
            bcc             : $this->bcc,
            body            : $this->body,
            cc              : $this->cc,
            headers         : $this->headers,
            html            : $this->html,
            replyTo         : $this->replyTo,
            subject         : $subject,
            to              : $this->to,
        );
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withTo(string $email, string $name = ''): static
    {
        return new static(
            enableExceptions: $this->enableExceptions,
            charset         : $this->charset,
            encoding        : $this->encoding,
            from            : $this->from,
            fromName        : $this->fromName,
            host            : $this->host,
            password        : $this->password,
            port            : $this->port,
            smtpAuth        : $this->smtpAuth,
            smtpSecure      : $this->smtpSecure,
            timeout         : $this->timeout,
            username        : $this->username,
            useSmtp         : $this->useSmtp,
            altBody         : $this->altBody,
            attachments     : $this->attachments,
            bcc             : $this->bcc,
            body            : $this->body,
            cc              : $this->cc,
            headers         : $this->headers,
            html            : $this->html,
            replyTo         : $this->replyTo,
            subject         : $this->subject,
            to              : [...$this->to, [$email, $name]],
        );
    }
}
