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

/**
 * The component's entry point.
 *
 * A mailer cannot work without an adapter, and the adapter is supplied once by
 * its factory through the constructor — it is not a mutable collaborator. The
 * message is passed per send, so no message state is held here and two sends
 * cannot bleed into one another.
 *
 * @api
 */
interface MailerInterface
{
    public function getAdapter(): Adapter\AdapterInterface;

    public function send(Adapter\MessageInterface $message): bool;
}
