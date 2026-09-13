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
 * Message-building surface. Every method returns a new instance, so a partially
 * configured message can be shared without one caller's changes reaching another.
 *
 * @api
 */
interface MessageInterface
{
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
