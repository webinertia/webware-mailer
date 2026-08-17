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

namespace WebwareTest\Mailer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use stdClass;
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\Mailer;
use Webware\Mailer\MailerAwareDelegator;
use Webware\Mailer\MailerAwareInterface;
use Webware\Mailer\MailerInterface;

#[CoversClass(MailerAwareDelegator::class)]
#[CoversMethod(MailerAwareDelegator::class, '__invoke')]
final class MailerAwareDelegatorTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \RuntimeException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeInjectsMailerIntoAwareService(): void
    {
        $mailer = new Mailer($this->createStub(AdapterInterface::class));

        $service = $this->createMock(MailerAwareInterface::class);
        $service->expects($this->once())->method('setMailer')->with($mailer);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(
                static fn(string $id): mixed => MailerInterface::class === $id ? $mailer : null,
            );

        $delegator = new MailerAwareDelegator();

        $this->assertSame($service, $delegator($container, name: 'service', callback: static fn(): object => $service));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \RuntimeException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeReturnsNonAwareServiceUntouched(): void
    {
        $service = new stdClass();

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('get');

        $delegator = new MailerAwareDelegator();

        $this->assertSame($service, $delegator($container, name: 'service', callback: static fn(): object => $service));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \RuntimeException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeThrowsWhenMailerIsNotMailerInstance(): void
    {
        $service = $this->createStub(MailerAwareInterface::class);
        $mailer = $this->createStub(MailerInterface::class);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(
                static fn(string $id): mixed => MailerInterface::class === $id ? $mailer : null,
            );

        $delegator = new MailerAwareDelegator();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs(
            'Delegator for MailerAwareInterface services requires a Webware\Mailer\Mailer instance.',
        );

        $delegator($container, name: 'service', callback: static fn(): object => $service);
    }
}
