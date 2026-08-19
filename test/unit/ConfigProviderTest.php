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
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\Adapter\MessageInterface;
use Webware\Mailer\Adapter\PhpMailer;
use Webware\Mailer\CommandBus\SendEmailCommand;
use Webware\Mailer\CommandBus\SendEmailCommandHandler;
use Webware\Mailer\CommandBus\SendEmailCommandHandlerFactory;
use Webware\Mailer\ConfigProvider;
use Webware\Mailer\Container\MailerFactory;
use Webware\Mailer\Container\PhpMailerFactory;
use Webware\Mailer\Mailer;
use Webware\Mailer\MailerInterface;
use Webware\Mailer\Middleware\MailerMiddleware;
use Webware\Mailer\Middleware\MailerMiddlewareFactory;
use Webware\MessageBus\ConfigProvider as BusProvider;
use Webware\MessageBus\MessageBusInterface;

use function dirname;

#[CoversClass(ConfigProvider::class)]
#[CoversMethod(ConfigProvider::class, 'getAdapterConfig')]
#[CoversMethod(ConfigProvider::class, 'getCommandMap')]
#[CoversMethod(ConfigProvider::class, 'getDependencies')]
#[CoversMethod(ConfigProvider::class, 'getMessageConfig')]
#[CoversMethod(ConfigProvider::class, 'getTemplates')]
#[CoversMethod(ConfigProvider::class, '__invoke')]
final class ConfigProviderTest extends TestCase
{
    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function getAdapterConfigReturnsDefaults(): void
    {
        $provider = new ConfigProvider();

        $this->assertSame(
            [
                'enableExceptions' => true,
                'useSmtp'          => false,
            ],
            $provider->getAdapterConfig(),
        );
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function getCommandMapMapsSendEmailCommandToHandler(): void
    {
        $provider = new ConfigProvider();

        $this->assertSame(
            [SendEmailCommand::class => SendEmailCommandHandler::class],
            $provider->getCommandMap(),
        );
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function getDependenciesReturnsExpectedServices(): void
    {
        $provider = new ConfigProvider();

        $this->assertSame(
            [
                'aliases'   => [
                    AdapterInterface::class => PhpMailer::class,
                    MailerInterface::class  => Mailer::class,
                ],
                'factories' => [
                    PhpMailer::class               => PhpMailerFactory::class,
                    SendEmailCommandHandler::class => SendEmailCommandHandlerFactory::class,
                    Mailer::class                  => MailerFactory::class,
                    MailerMiddleware::class        => MailerMiddlewareFactory::class,
                ],
            ],
            $provider->getDependencies(),
        );
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function getMessageConfigReturnsEmptyArray(): void
    {
        $provider = new ConfigProvider();

        $this->assertSame([], $provider->getMessageConfig());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function getTemplatesReturnsMailPath(): void
    {
        $provider = new ConfigProvider();

        $this->assertSame(
            ['paths' => ['mail' => [dirname(__DIR__, levels: 2) . '/src/../templates/']]],
            $provider->getTemplates(),
        );
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeMergesAllConfiguration(): void
    {
        $provider = new ConfigProvider();
        $config   = $provider();

        $this->assertSame($provider->getDependencies(), $config['dependencies'] ?? null);
        $this->assertSame($provider->getTemplates(), $config['templates'] ?? null);
        $this->assertSame(
            [BusProvider::COMMAND_MAP_KEY => $provider->getCommandMap()],
            $config[MessageBusInterface::class] ?? null,
        );
        $this->assertSame($provider->getAdapterConfig(), $config[AdapterInterface::class] ?? null);
        $this->assertSame($provider->getMessageConfig(), $config[MessageInterface::class] ?? null);
    }
}
