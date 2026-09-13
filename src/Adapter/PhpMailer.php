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
 * The settings passed to the constructor are exposed as read-only properties and
 * every message-building method returns a new instance over a cloned transport,
 * so a configured adapter can be shared and reused without one send leaking into
 * the next.
 */
final class PhpMailer implements AdapterInterface
{
    public function __construct(
        private BaseMailer $mailer,
        public private(set) bool $enableExceptions = true,
        public private(set) string $charset = 'iso-8859-1',
        public private(set) string $encoding = '8bit',
        public private(set) string $from = '',
        public private(set) string $fromName = '',
        public private(set) string $host = 'localhost',
        #[SensitiveParameter]
        public private(set) string $password = '',
        public private(set) int $port = 25,
        public private(set) bool $smtpAuth = false,
        public private(set) string $smtpSecure = '',
        public private(set) int $timeout = 300,
        public private(set) string $username = '',
        public private(set) bool $useSmtp = false,
    ) {}

    #[Override]
    public function isMail(): self
    {
        $this->mailer->isMail();

        return $this;
    }

    #[Override]
    public function isSmtp(): self
    {
        $this->mailer->isSMTP();

        return $this;
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function send(): bool
    {
        return $this->mailer->send();
    }

    #[Override]
    public function withAltBody(string $altBody): static
    {
        $clone                  = $this->replicate();
        $clone->mailer->AltBody = $altBody;

        return $clone;
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withAttachment(string $path, string $name = '', string $mimeType = ''): static
    {
        $clone = $this->replicate();
        $clone->mailer->addAttachment($path, $name, encoding: 'base64', type: $mimeType);

        return $clone;
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withAttachmentFromString(string $content, string $name, string $mimeType = ''): static
    {
        $clone = $this->replicate();
        $clone->mailer->addStringAttachment($content, $name, encoding: 'base64', type: $mimeType);

        return $clone;
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withBcc(string $email, string $name = ''): static
    {
        $clone = $this->replicate();
        $clone->mailer->addBCC($email, $name);

        return $clone;
    }

    #[Override]
    public function withBody(string $body): static
    {
        $clone               = $this->replicate();
        $clone->mailer->Body = $body;

        return $clone;
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withCc(string $email, string $name = ''): static
    {
        $clone = $this->replicate();
        $clone->mailer->addCC($email, $name);

        return $clone;
    }

    #[Override]
    public function withCharset(string $charset): static
    {
        $clone                  = $this->replicate();
        $clone->mailer->CharSet = $charset;

        return $clone;
    }

    #[Override]
    public function withEncoding(string $encoding): static
    {
        $clone                   = $this->replicate();
        $clone->mailer->Encoding = $encoding;

        return $clone;
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withFrom(string $email, string $name = ''): static
    {
        $clone = $this->replicate();
        $clone->mailer->setFrom($email, $name);

        return $clone;
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withHeader(string $name, string $value): static
    {
        $clone = $this->replicate();
        $clone->mailer->addCustomHeader($name, $value);

        return $clone;
    }

    #[Override]
    public function withHtml(bool $flag = true): static
    {
        $clone = $this->replicate();
        $clone->mailer->isHTML($flag);

        return $clone;
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withReplyTo(string $email, string $name = ''): static
    {
        $clone = $this->replicate();
        $clone->mailer->addReplyTo($email, $name);

        return $clone;
    }

    #[Override]
    public function withSubject(string $subject): static
    {
        $clone                  = $this->replicate();
        $clone->mailer->Subject = $subject;

        return $clone;
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function withTo(string $email, string $name = ''): static
    {
        $clone = $this->replicate();
        $clone->mailer->addAddress($email, $name);

        return $clone;
    }

    /**
     * Clone the adapter over a cloned transport, so a change made through a
     * `with*()` method cannot reach the instance the caller already holds.
     */
    private function replicate(): static
    {
        $clone         = clone $this;
        $clone->mailer = clone $this->mailer;

        return $clone;
    }
}
