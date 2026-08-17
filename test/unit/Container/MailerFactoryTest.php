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

namespace WebwareTest\Mailer\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\Container\MailerFactory;
use Webware\Mailer\Mailer;
use Webware\Mailer\MailerInterface;

#[CoversClass(MailerFactory::class)]
#[CoversMethod(MailerFactory::class, '__invoke')]
final class MailerFactoryTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeReturnsMailerWithAdapter(): void
    {
        $adapter = $this->createStub(AdapterInterface::class);
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(
                static fn(string $id): mixed => AdapterInterface::class === $id ? $adapter : null,
            );

        $factory = new MailerFactory();
        $result = $factory($container);

        $this->assertInstanceOf(Mailer::class, $result);
        $this->assertInstanceOf(MailerInterface::class, $result);
        $this->assertSame($adapter, $result->getAdapter());
    }
}
