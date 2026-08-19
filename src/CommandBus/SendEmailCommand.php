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

namespace Webware\Mailer\CommandBus;

use InvalidArgumentException;
use Override;
use Webware\Mailer\Event\MessageEvent;
use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\Event\EventAwareInterface;
use Webware\MessageBus\Event\EventInterface;

final class SendEmailCommand implements CommandInterface, EventAwareInterface
{
    public function __construct(
        private string $to,
        private string $from,
        private string $subject,
        private string $body,
        private MessageEvent $event,
    ) {}

    public function getBody(): string
    {
        return $this->body;
    }

    #[Override]
    public function getEvent(): MessageEvent
    {
        return $this->event;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * @throws InvalidArgumentException If the event is not a MessageEvent instance.
     */
    #[Override]
    public function setEvent(EventInterface $event): void
    {
        if (! $event instanceof MessageEvent) {
            throw new InvalidArgumentException('SendEmailCommand requires a MessageEvent instance.');
        }

        $this->event = $event;
    }
}
