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

namespace WebwareTest\Mailer\Http\Middleware\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionProperty;
use Webware\Mailer\Http\Middleware\Container\MailerMiddlewareFactory;
use Webware\Mailer\Http\Middleware\MailerMiddleware;
use Webware\Mailer\MailerInterface;

#[CoversClass(MailerMiddlewareFactory::class)]
#[CoversMethod(MailerMiddlewareFactory::class, '__invoke')]
final class MailerMiddlewareFactoryTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeBuildsTheMiddlewareFromTheMailerService(): void
    {
        $mailer    = $this->createStub(MailerInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(MailerInterface::class)
            ->willReturn($mailer);

        $middleware = (new MailerMiddlewareFactory())($container);

        $this->assertInstanceOf(MailerMiddleware::class, $middleware);
        $this->assertSame(
            $mailer,
            new ReflectionProperty(MailerMiddleware::class, 'mailer')->getValue($middleware),
        );
    }
}
