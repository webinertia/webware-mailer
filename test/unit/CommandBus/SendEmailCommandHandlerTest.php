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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\CommandBus\SendEmailCommand;
use Webware\Mailer\CommandBus\SendEmailCommandHandler;
use Webware\Mailer\Event\MessageEvent;
use Webware\Mailer\MailerInterface;
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
    public function handleReturnsSuccessResultWhenMailSent(): void
    {
        $command = $this->makeCommand();
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())->method('to')->with('to@example.com')->willReturnSelf();
        $adapter->expects($this->once())->method('from')->with('from@example.com')->willReturnSelf();
        $adapter->expects($this->once())->method('subject')->with('Subject')->willReturnSelf();
        $adapter->expects($this->once())->method('body')->with('Body')->willReturnSelf();

        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('getAdapter')->willReturn($adapter);
        $mailer->method('send')->willReturn(true);

        $handler = new SendEmailCommandHandler($mailer);
        $result = $handler->handle($command);

        $this->assertInstanceOf(CommandResultInterface::class, $result);
        $this->assertSame($command, $result->getCommand());
        $this->assertSame(MessageStatus::Success, $result->getStatus());
        $this->assertSame('Email sent successfully', $result->getResult());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function handleReturnsFailureResultWhenNoAdapterConfigured(): void
    {
        $command = $this->makeCommand();

        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('getAdapter')->willReturn(null);

        $handler = new SendEmailCommandHandler($mailer);
        $result = $handler->handle($command);

        $this->assertSame(MessageStatus::Failure, $result->getStatus());
        $this->assertSame('No adapter configured on Mailer instance.', $result->getResult());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function handleReturnsFailureResultWhenSendThrows(): void
    {
        $command = $this->makeCommand();
        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('to')->willReturnSelf();
        $adapter->method('from')->willReturnSelf();
        $adapter->method('subject')->willReturnSelf();
        $adapter->method('body')->willReturnSelf();

        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('getAdapter')->willReturn($adapter);
        $mailer->method('send')->willThrowException(new RuntimeException('boom'));

        $handler = new SendEmailCommandHandler($mailer);
        $result = $handler->handle($command);

        $this->assertSame(MessageStatus::Failure, $result->getStatus());
        $this->assertSame('boom', $result->getResult());
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
