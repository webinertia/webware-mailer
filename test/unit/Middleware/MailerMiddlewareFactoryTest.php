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

namespace WebwareTest\Mailer\Middleware;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\ConfigProvider;
use Webware\Mailer\MailerInterface;
use Webware\Mailer\Middleware\MailerMiddleware;
use Webware\Mailer\Middleware\MailerMiddlewareFactory;

#[CoversClass(MailerMiddlewareFactory::class)]
#[CoversMethod(MailerMiddlewareFactory::class, '__invoke')]
final class MailerMiddlewareFactoryTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeReturnsMailerMiddleware(): void
    {
        $mailer = $this->createStub(MailerInterface::class);
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(
                static function (string $id) use ($mailer): mixed {
                    if (MailerInterface::class === $id) {
                        return $mailer;
                    }

                    if ('config' === $id) {
                        return [
                            ConfigProvider::class => [
                                AdapterInterface::class => [
                                    'from' => 'sender@example.com',
                                ],
                            ],
                        ];
                    }

                    return null;
                },
            );

        $factory = new MailerMiddlewareFactory();
        $result = $factory($container);

        $this->assertInstanceOf(MailerMiddleware::class, $result);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeThrowsWhenAdapterSettingsMissing(): void
    {
        $mailer = $this->createStub(MailerInterface::class);
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(
                static function (string $id) use ($mailer): mixed {
                    if (MailerInterface::class === $id) {
                        return $mailer;
                    }

                    if ('config' === $id) {
                        return [
                            ConfigProvider::class => [],
                        ];
                    }

                    return null;
                },
            );

        $factory = new MailerMiddlewareFactory();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Service: ' . AdapterInterface::class . ' configuration must be an array.');

        $factory($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeThrowsWhenMailerConfigIsNotArray(): void
    {
        $mailer = $this->createStub(MailerInterface::class);
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(
                static function (string $id) use ($mailer): mixed {
                    if (MailerInterface::class === $id) {
                        return $mailer;
                    }

                    if ('config' === $id) {
                        return [
                            ConfigProvider::class => 'not-an-array',
                        ];
                    }

                    return null;
                },
            );

        $factory = new MailerMiddlewareFactory();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('Service: ' . ConfigProvider::class . ' configuration must be an array.');

        $factory($container);
    }
}
