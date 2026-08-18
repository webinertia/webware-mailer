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

/** @api */
interface MailerInterface
{
    public function getAdapter(): ?Adapter\AdapterInterface;

    public function send(): bool;

    public function setAdapter(Adapter\AdapterInterface $adapter): self;
}
