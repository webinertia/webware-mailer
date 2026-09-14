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

final class Mailer implements MailerInterface
{
    public function __construct(
        private readonly Adapter\AdapterInterface $adapter,
    ) {}

    #[Override]
    public function getAdapter(): Adapter\AdapterInterface
    {
        return $this->adapter;
    }

    #[Override]
    public function send(Adapter\MessageInterface $message): bool
    {
        return $this->adapter->send($message);
    }
}
