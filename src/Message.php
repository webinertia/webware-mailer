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

namespace Webware\Mailer;

use Override;
use Webware\Mailer\Adapter\MessageInterface;

/**
 * The message side of the package: content that accumulates and is discarded
 * once sent. Nothing here knows about a transport.
 *
 * Every method returns a new instance, so a partially built message can be shared
 * without one caller's changes reaching another. `charset` and `encoding` are
 * message properties — they describe how the content is written, not how it is
 * carried — and an empty value leaves the choice to the adapter's library.
 *
 * @api
 */
final class Message implements MessageInterface
{
    /**
     * @param list<array{0: string, 1: string, 2: string, 3: bool}> $attachments content, name, mime type, is raw content
     * @param list<array{0: string, 1: string}>                    $bcc         email, name
     * @param list<array{0: string, 1: string}>                    $cc          email, name
     * @param list<array{0: string, 1: string}>                    $headers     name, value
     * @param list<array{0: string, 1: string}>                    $replyTo     email, name
     * @param list<array{0: string, 1: string}>                    $to          email, name
     */
    public function __construct(
        public private(set) string $altBody = '',
        public private(set) array $attachments = [],
        public private(set) array $bcc = [],
        public private(set) string $body = '',
        public private(set) array $cc = [],
        public private(set) string $charset = '',
        public private(set) string $encoding = '',
        public private(set) string $from = '',
        public private(set) string $fromName = '',
        public private(set) array $headers = [],
        public private(set) bool $html = false,
        public private(set) array $replyTo = [],
        public private(set) string $subject = '',
        public private(set) array $to = [],
    ) {}

    #[Override]
    public function withAltBody(string $altBody): static
    {
        return new static(
            altBody    : $altBody,
            attachments: $this->attachments,
            bcc        : $this->bcc,
            body       : $this->body,
            cc         : $this->cc,
            charset    : $this->charset,
            encoding   : $this->encoding,
            from       : $this->from,
            fromName   : $this->fromName,
            headers    : $this->headers,
            html       : $this->html,
            replyTo    : $this->replyTo,
            subject    : $this->subject,
            to         : $this->to,
        );
    }

    #[Override]
    public function withAttachment(string $path, string $name = '', string $mimeType = ''): static
    {
        return new static(
            altBody    : $this->altBody,
            attachments: [...$this->attachments, [$path, $name, $mimeType, false]],
            bcc        : $this->bcc,
            body       : $this->body,
            cc         : $this->cc,
            charset    : $this->charset,
            encoding   : $this->encoding,
            from       : $this->from,
            fromName   : $this->fromName,
            headers    : $this->headers,
            html       : $this->html,
            replyTo    : $this->replyTo,
            subject    : $this->subject,
            to         : $this->to,
        );
    }

    #[Override]
    public function withAttachmentFromString(string $content, string $name, string $mimeType = ''): static
    {
        return new static(
            altBody    : $this->altBody,
            attachments: [...$this->attachments, [$content, $name, $mimeType, true]],
            bcc        : $this->bcc,
            body       : $this->body,
            cc         : $this->cc,
            charset    : $this->charset,
            encoding   : $this->encoding,
            from       : $this->from,
            fromName   : $this->fromName,
            headers    : $this->headers,
            html       : $this->html,
            replyTo    : $this->replyTo,
            subject    : $this->subject,
            to         : $this->to,
        );
    }

    #[Override]
    public function withBcc(string $email, string $name = ''): static
    {
        return new static(
            altBody    : $this->altBody,
            attachments: $this->attachments,
            bcc        : [...$this->bcc, [$email, $name]],
            body       : $this->body,
            cc         : $this->cc,
            charset    : $this->charset,
            encoding   : $this->encoding,
            from       : $this->from,
            fromName   : $this->fromName,
            headers    : $this->headers,
            html       : $this->html,
            replyTo    : $this->replyTo,
            subject    : $this->subject,
            to         : $this->to,
        );
    }

    #[Override]
    public function withBody(string $body): static
    {
        return new static(
            altBody    : $this->altBody,
            attachments: $this->attachments,
            bcc        : $this->bcc,
            body       : $body,
            cc         : $this->cc,
            charset    : $this->charset,
            encoding   : $this->encoding,
            from       : $this->from,
            fromName   : $this->fromName,
            headers    : $this->headers,
            html       : $this->html,
            replyTo    : $this->replyTo,
            subject    : $this->subject,
            to         : $this->to,
        );
    }

    #[Override]
    public function withCc(string $email, string $name = ''): static
    {
        return new static(
            altBody    : $this->altBody,
            attachments: $this->attachments,
            bcc        : $this->bcc,
            body       : $this->body,
            cc         : [...$this->cc, [$email, $name]],
            charset    : $this->charset,
            encoding   : $this->encoding,
            from       : $this->from,
            fromName   : $this->fromName,
            headers    : $this->headers,
            html       : $this->html,
            replyTo    : $this->replyTo,
            subject    : $this->subject,
            to         : $this->to,
        );
    }

    #[Override]
    public function withCharset(string $charset): static
    {
        return new static(
            altBody    : $this->altBody,
            attachments: $this->attachments,
            bcc        : $this->bcc,
            body       : $this->body,
            cc         : $this->cc,
            charset    : $charset,
            encoding   : $this->encoding,
            from       : $this->from,
            fromName   : $this->fromName,
            headers    : $this->headers,
            html       : $this->html,
            replyTo    : $this->replyTo,
            subject    : $this->subject,
            to         : $this->to,
        );
    }

    #[Override]
    public function withEncoding(string $encoding): static
    {
        return new static(
            altBody    : $this->altBody,
            attachments: $this->attachments,
            bcc        : $this->bcc,
            body       : $this->body,
            cc         : $this->cc,
            charset    : $this->charset,
            encoding   : $encoding,
            from       : $this->from,
            fromName   : $this->fromName,
            headers    : $this->headers,
            html       : $this->html,
            replyTo    : $this->replyTo,
            subject    : $this->subject,
            to         : $this->to,
        );
    }

    #[Override]
    public function withFrom(string $email, string $name = ''): static
    {
        return new static(
            altBody    : $this->altBody,
            attachments: $this->attachments,
            bcc        : $this->bcc,
            body       : $this->body,
            cc         : $this->cc,
            charset    : $this->charset,
            encoding   : $this->encoding,
            from       : $email,
            fromName   : $name,
            headers    : $this->headers,
            html       : $this->html,
            replyTo    : $this->replyTo,
            subject    : $this->subject,
            to         : $this->to,
        );
    }

    #[Override]
    public function withHeader(string $name, string $value): static
    {
        return new static(
            altBody    : $this->altBody,
            attachments: $this->attachments,
            bcc        : $this->bcc,
            body       : $this->body,
            cc         : $this->cc,
            charset    : $this->charset,
            encoding   : $this->encoding,
            from       : $this->from,
            fromName   : $this->fromName,
            headers    : [...$this->headers, [$name, $value]],
            html       : $this->html,
            replyTo    : $this->replyTo,
            subject    : $this->subject,
            to         : $this->to,
        );
    }

    #[Override]
    public function withHtml(bool $flag = true): static
    {
        return new static(
            altBody    : $this->altBody,
            attachments: $this->attachments,
            bcc        : $this->bcc,
            body       : $this->body,
            cc         : $this->cc,
            charset    : $this->charset,
            encoding   : $this->encoding,
            from       : $this->from,
            fromName   : $this->fromName,
            headers    : $this->headers,
            html       : $flag,
            replyTo    : $this->replyTo,
            subject    : $this->subject,
            to         : $this->to,
        );
    }

    #[Override]
    public function withReplyTo(string $email, string $name = ''): static
    {
        return new static(
            altBody    : $this->altBody,
            attachments: $this->attachments,
            bcc        : $this->bcc,
            body       : $this->body,
            cc         : $this->cc,
            charset    : $this->charset,
            encoding   : $this->encoding,
            from       : $this->from,
            fromName   : $this->fromName,
            headers    : $this->headers,
            html       : $this->html,
            replyTo    : [...$this->replyTo, [$email, $name]],
            subject    : $this->subject,
            to         : $this->to,
        );
    }

    #[Override]
    public function withSubject(string $subject): static
    {
        return new static(
            altBody    : $this->altBody,
            attachments: $this->attachments,
            bcc        : $this->bcc,
            body       : $this->body,
            cc         : $this->cc,
            charset    : $this->charset,
            encoding   : $this->encoding,
            from       : $this->from,
            fromName   : $this->fromName,
            headers    : $this->headers,
            html       : $this->html,
            replyTo    : $this->replyTo,
            subject    : $subject,
            to         : $this->to,
        );
    }

    #[Override]
    public function withTo(string $email, string $name = ''): static
    {
        return new static(
            altBody    : $this->altBody,
            attachments: $this->attachments,
            bcc        : $this->bcc,
            body       : $this->body,
            cc         : $this->cc,
            charset    : $this->charset,
            encoding   : $this->encoding,
            from       : $this->from,
            fromName   : $this->fromName,
            headers    : $this->headers,
            html       : $this->html,
            replyTo    : $this->replyTo,
            subject    : $this->subject,
            to         : [...$this->to, [$email, $name]],
        );
    }
}
