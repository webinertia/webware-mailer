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

/**
 * Message-building surface: content only, no transport. Every method returns a
 * new instance that carries the state of the instance it was called on, so a
 * partially configured message can be shared without one caller's changes
 * reaching another. {@see \Webware\Mailer\Message} is this package's
 * implementation.
 *
 * @api
 */
interface MessageInterface
{
    /** Plain-text alternative body. */
    public string $altBody { get; }

    /** @var list<array{0: string, 1: string, 2: string, 3: bool}> content, name, mime type, is raw content */
    public array $attachments { get; }

    /** @var list<array{0: string, 1: string}> email, name */
    public array $bcc { get; }

    public string $body { get; }

    /** @var list<array{0: string, 1: string}> email, name */
    public array $cc { get; }

    /** Character set; empty leaves the choice to the adapter's library. */
    public string $charset { get; }

    /** Content transfer encoding; empty leaves the choice to the adapter's library. */
    public string $encoding { get; }

    /** Sender address. */
    public string $from { get; }

    /** Sender display name. */
    public string $fromName { get; }

    /** @var list<array{0: string, 1: string}> header, value */
    public array $headers { get; }

    /** Whether the body is sent as HTML. */
    public bool $html { get; }

    /** @var list<array{0: string, 1: string}> email, name */
    public array $replyTo { get; }

    public string $subject { get; }

    /** @var list<array{0: string, 1: string}> email, name */
    public array $to { get; }

    public function withAltBody(string $altBody): static;

    public function withAttachment(string $path, string $name = '', string $mimeType = ''): static;

    public function withAttachmentFromString(string $content, string $name, string $mimeType = ''): static;

    public function withBcc(string $email, string $name = ''): static;

    public function withBody(string $body): static;

    public function withCc(string $email, string $name = ''): static;

    public function withCharset(string $charset): static;

    public function withEncoding(string $encoding): static;

    public function withFrom(string $email, string $name = ''): static;

    public function withHeader(string $name, string $value): static;

    public function withHtml(bool $flag = true): static;

    public function withReplyTo(string $email, string $name = ''): static;

    public function withSubject(string $subject): static;

    public function withTo(string $email, string $name = ''): static;
}
