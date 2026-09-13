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
 * Adapter contract.
 *
 * The settings the adapter was configured with are exposed as read-only
 * properties, so consumers read typed values instead of raw configuration
 * arrays. Implementations are free to publish additional optional properties of
 * their own, but the ones declared here are the minimum an implementation needs.
 *
 * @api
 */
interface AdapterInterface extends MessageInterface
{
    public bool $enableExceptions { get; }

    public string $charset { get; }

    public string $encoding { get; }

    public string $from { get; }

    public string $host { get; }

    public string $password { get; }

    public int $port { get; }

    public bool $smtpAuth { get; }

    public string $smtpSecure { get; }

    public int $timeout { get; }

    public string $username { get; }

    public bool $useSmtp { get; }

    public function isMail(): self;

    public function isSmtp(): self;

    public function send(): bool;
}
