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
 * Transport contract.
 *
 * An adapter owns a configured transport and sends messages over it. It is built
 * by its factory from that implementation's own configuration shape, so this
 * contract describes nothing about the transport's settings: the factory is the
 * only consumer of those, and they stay on the concrete adapter.
 *
 * The two supported libraries share no exception interface, so implementations
 * may throw their own library's transport exception; callers that need to handle
 * failures should catch per implementation.
 *
 * @api
 */
interface AdapterInterface
{
    public function send(MessageInterface $message): bool;
}
