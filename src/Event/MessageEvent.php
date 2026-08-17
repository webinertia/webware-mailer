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

use Webware\CommandBus\Event\Event;

// todo: determine whether this event is still needed and flesh out accordingly
class MessageEvent extends Event
{
    final public const EVENT_EMAIL_MESSAGE = 'emailMessage';
}
