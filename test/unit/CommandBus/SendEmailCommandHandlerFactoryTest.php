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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Mailer\CommandBus\SendEmailCommandHandler;
use Webware\Mailer\CommandBus\SendEmailCommandHandlerFactory;
use Webware\Mailer\MailerInterface;

#[CoversClass(SendEmailCommandHandlerFactory::class)]
#[CoversMethod(SendEmailCommandHandlerFactory::class, '__invoke')]
final class SendEmailCommandHandlerFactoryTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeReturnsSendEmailCommandHandler(): void
    {
        $mailer    = $this->createStub(MailerInterface::class);
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(
                static fn(string $id): mixed => MailerInterface::class === $id ? $mailer : null,
            );

        $factory = new SendEmailCommandHandlerFactory();
        $result  = $factory($container);

        $this->assertInstanceOf(SendEmailCommandHandler::class, $result);
    }
}
