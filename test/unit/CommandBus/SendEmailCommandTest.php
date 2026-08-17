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

namespace WebwareTest\Mailer\CommandBus;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Mailer\CommandBus\SendEmailCommand;
use Webware\Mailer\Event\MessageEvent;
use Webware\MessageBus\Event\Event;

#[CoversClass(SendEmailCommand::class)]
#[CoversMethod(SendEmailCommand::class, 'getBody')]
#[CoversMethod(SendEmailCommand::class, 'getEvent')]
#[CoversMethod(SendEmailCommand::class, 'getFrom')]
#[CoversMethod(SendEmailCommand::class, 'getSubject')]
#[CoversMethod(SendEmailCommand::class, 'getTo')]
#[CoversMethod(SendEmailCommand::class, 'setEvent')]
final class SendEmailCommandTest extends TestCase
{
    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function gettersReturnConstructorValues(): void
    {
        $event = new MessageEvent();
        $command = new SendEmailCommand(
            'to@example.com',
            'from@example.com',
            'Subject',
            'Body',
            $event,
        );

        $this->assertSame('to@example.com', $command->getTo());
        $this->assertSame('from@example.com', $command->getFrom());
        $this->assertSame('Subject', $command->getSubject());
        $this->assertSame('Body', $command->getBody());
        $this->assertSame($event, $command->getEvent());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function setEventReplacesMessageEvent(): void
    {
        $command = new SendEmailCommand(
            'to@example.com',
            'from@example.com',
            'Subject',
            'Body',
            new MessageEvent('first'),
        );
        $replacement = new MessageEvent('second');

        $command->setEvent($replacement);

        $this->assertSame($replacement, $command->getEvent());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function setEventRejectsNonMessageEvent(): void
    {
        $command = new SendEmailCommand(
            'to@example.com',
            'from@example.com',
            'Subject',
            'Body',
            new MessageEvent(),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('SendEmailCommand requires a MessageEvent instance.');

        $command->setEvent(new Event('other'));
    }
}
