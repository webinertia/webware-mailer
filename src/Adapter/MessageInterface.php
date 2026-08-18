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

/** @api */
interface MessageInterface
{
    public function addHeader(string $name, string $value): self;

    public function altBody(string $altBody): self;

    public function attach(string $path, string $name = '', string $mimeType = ''): self;

    public function attachFromString(string $content, string $name, string $mimeType = ''): self;

    public function bcc(string $email, string $name = ''): self;

    public function body(string $body): self;

    public function cc(string $email, string $name = ''): self;

    public function charset(string $charset): self;

    public function encoding(string $encoding): self;

    public function from(string $email, string $name = ''): self;

    public function isHtml(bool $flag = true): self;

    public function replyTo(string $email, string $name = ''): self;

    public function reset(): self;

    public function subject(string $subject): self;

    public function to(string $email, string $name = ''): self;
}
