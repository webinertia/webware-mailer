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

final class PhpMailer implements AdapterInterface
{
    public function __construct(
        private BaseMailer $mailer,
    ) {}

    /**
     * @throws MailerException
     */
    #[Override]
    public function addHeader(string $name, string $value): self
    {
        $this->mailer->addCustomHeader($name, $value);

        return $this;
    }

    #[Override]
    public function altBody(string $altBody): self
    {
        $this->mailer->AltBody = $altBody;

        return $this;
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function attach(string $path, string $name = '', string $mimeType = ''): self
    {
        $this->mailer->addAttachment($path, $name, encoding: 'base64', type: $mimeType);

        return $this;
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function attachFromString(string $content, string $name, string $mimeType = ''): self
    {
        $this->mailer->addStringAttachment($content, $name, encoding: 'base64', type: $mimeType);

        return $this;
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function bcc(string $email, string $name = ''): self
    {
        $this->mailer->addBCC($email, $name);

        return $this;
    }

    #[Override]
    public function body(string $body): self
    {
        $this->mailer->Body = $body;

        return $this;
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function cc(string $email, string $name = ''): self
    {
        $this->mailer->addCC($email, $name);

        return $this;
    }

    #[Override]
    public function charset(string $charset): self
    {
        $this->mailer->CharSet = $charset;

        return $this;
    }

    #[Override]
    public function encoding(string $encoding): self
    {
        $this->mailer->Encoding = $encoding;

        return $this;
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function from(string $email, string $name = ''): self
    {
        $this->mailer->setFrom($email, $name);

        return $this;
    }

    #[Override]
    public function isHtml(bool $flag = true): self
    {
        $this->mailer->isHTML($flag);

        return $this;
    }

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
    public function replyTo(string $email, string $name = ''): self
    {
        $this->mailer->addReplyTo($email, $name);

        return $this;
    }

    #[Override]
    public function reset(): self
    {
        $this->mailer->clearAddresses();
        $this->mailer->clearCCs();
        $this->mailer->clearBCCs();
        $this->mailer->clearReplyTos();
        $this->mailer->clearAttachments();
        $this->mailer->clearCustomHeaders();
        $this->mailer->Subject = '';
        $this->mailer->Body = '';
        $this->mailer->AltBody = '';

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
    public function subject(string $subject): self
    {
        $this->mailer->Subject = $subject;

        return $this;
    }

    /**
     * @throws MailerException
     */
    #[Override]
    public function to(string $email, string $name = ''): self
    {
        $this->mailer->addAddress($email, $name);

        return $this;
    }
}
