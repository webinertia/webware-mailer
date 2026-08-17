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

namespace Webware\Mailer;

use Webware\CommandBus\CommandBusInterface;
use Webware\CommandBus\ConfigProvider as BusProvider;
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\Adapter\MessageInterface;

final readonly class ConfigProvider
{
    /** @return array<string, mixed> */
    public function getAdapterConfig(): array
    {
        return [
            'enableExceptions' => true,
            'useSmtp' => false,
        ];
    }

    /** @return array<string, mixed> */
    public function getCommandMap(): array
    {
        return [
            CommandBus\SendEmailCommand::class => CommandBus\SendEmailCommandHandler::class,
        ];
    }

    /** @return array<string, mixed> */
    public function getDependencies(): array
    {
        return [
            'aliases' => [
                AdapterInterface::class => Adapter\PhpMailer::class, // required mapping
                MailerInterface::class => Mailer::class,
            ],
            'factories' => [
                Adapter\PhpMailer::class => Container\PhpMailerFactory::class,
                CommandBus\SendEmailCommandHandler::class => CommandBus\SendEmailCommandHandlerFactory::class,
                Mailer::class => Container\MailerFactory::class,
                Middleware\MailerMiddleware::class => Middleware\MailerMiddlewareFactory::class,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function getMessageConfig(): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function getTemplates(): array
    {
        return [
            'paths' => [
                'mail' => [__DIR__ . '/../templates/'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates' => $this->getTemplates(),
            CommandBusInterface::class => [
                BusProvider::COMMAND_MAP_KEY => $this->getCommandMap(),
            ],
            AdapterInterface::class => $this->getAdapterConfig(),
            MessageInterface::class => $this->getMessageConfig(),
        ];
    }
}
