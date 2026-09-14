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

namespace WebwareTest\Mailer\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webware\Mailer\Command\SendEmailCommand;
use Webware\Mailer\CommandHandler\SendEmailCommandHandler;
use Webware\Mailer\Event\MessageEvent;
use Webware\Mailer\MailerInterface;
use Webware\Mailer\Message;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\MessageStatus;

#[CoversClass(SendEmailCommandHandler::class)]
#[CoversMethod(SendEmailCommandHandler::class, 'handle')]
final class SendEmailCommandHandlerTest extends TestCase
{
    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function handleReturnsFailureResultWhenSendThrows(): void
    {
        $command = $this->makeCommand();

        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('send')->willThrowException(new RuntimeException('boom'));

        $result = new SendEmailCommandHandler($mailer)->handle($command);

        $this->assertSame(MessageStatus::Failure, $result->getStatus());
        $this->assertSame('boom', $result->getResult());
    }

    /**
     * The handler builds the message from the command and hands it to the mailer;
     * the adapter it may be holding is the mailer's business, not the handler's.
     *
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function handleReturnsSuccessResultWhenMailSent(): void
    {
        $command = $this->makeCommand();

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(
                static fn(Message $message): bool => (
                    [['to@example.com', '']] === $message->to
                    && 'from@example.com' === $message->from
                    && 'Subject' === $message->subject
                    && 'Body' === $message->body
                ),
            ))
            ->willReturn(true);

        $result = new SendEmailCommandHandler($mailer)->handle($command);

        $this->assertInstanceOf(CommandResultInterface::class, $result);
        $this->assertSame($command, $result->getCommand());
        $this->assertSame(MessageStatus::Success, $result->getStatus());
        $this->assertSame('Email sent successfully', $result->getResult());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    private function makeCommand(): SendEmailCommand
    {
        return new SendEmailCommand(
            'to@example.com',
            'from@example.com',
            'Subject',
            'Body',
            new MessageEvent(),
        );
    }
}
