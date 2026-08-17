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
use RuntimeException;

final readonly class Mailer implements MailerInterface
{
    public function __construct(
        private ?Adapter\AdapterInterface $adapter,
    ) {}

    #[Override]
    public function getAdapter(): ?Adapter\AdapterInterface
    {
        return $this->adapter;
    }

    #[Override]
    public function send(): bool
    {
        if ($this->adapter === null) {
            throw new RuntimeException('No adapter configured on Mailer instance.');
        }

        return $this->adapter->send();
    }

    #[Override]
    public function setAdapter(Adapter\AdapterInterface $adapter): self
    {
        $this->adapter = $adapter;

        return $this;
    }
}
