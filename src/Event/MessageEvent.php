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

namespace Webware\Mailer\Event;

use Webware\MessageBus\Event\Event;

// TODO(@tyrsson): determine whether this event is still needed and flesh out accordingly
final class MessageEvent extends Event
{
    final public const string EVENT_EMAIL_MESSAGE = 'emailMessage';
}
