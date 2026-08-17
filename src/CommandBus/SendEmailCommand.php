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

use Override;
use Webware\CommandBus\CommandInterface;
use Webware\CommandBus\Event\EventAwareInterface;
use Webware\CommandBus\Event\EventInterface;
use Webware\Mailer\Event\MessageEvent;

final readonly class SendEmailCommand implements CommandInterface, EventAwareInterface
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

    #[Override]
    public function setEvent(EventInterface|MessageEvent $event): void {}
}
