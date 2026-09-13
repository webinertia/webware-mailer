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

use Webware\Mailer\Adapter\AdapterInterface;
use Webware\MessageBus\ConfigProvider as BusProvider;
use Webware\MessageBus\MessageBusInterface;

/**
 * Adapter configuration shape.
 *
 * Every key is optional: the published defaults below are merged with the host's
 * own values, and the adapter factory applies a default for each key it does not
 * find. The typed adapter contract on `AdapterInterface` is what consumers read
 * once the adapter has been built from this section.
 *
 * @type AdapterConfig = array{
 *   enableExceptions?: bool,
 *   useSmtp?: bool,
 *   host?: string,
 *   port?: int,
 *   smtp_auth?: bool,
 *   username?: string,
 *   password?: string,
 *   charset?: string,
 *   encoding?: string,
 *   timeout?: int,
 *   smtp_secure?: string,
 *   from?: string,
 * }
 */
final readonly class ConfigProvider
{
    /** @return AdapterConfig */
    public function getAdapterConfig(): array
    {
        return [
            'enableExceptions' => true,
            'useSmtp'          => false,
        ];
    }

    /** @return array<string, mixed> */
    public function getCommandMap(): array
    {
        return [
            Command\SendEmailCommand::class => CommandHandler\SendEmailCommandHandler::class,
        ];
    }

    /** @return array<string, mixed> */
    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                AdapterInterface::class => Adapter\PhpMailer::class, // required mapping
                MailerInterface::class => Mailer::class,
            ],
            'factories' => [
                Adapter\PhpMailer::class                      => Container\PhpMailerFactory::class,
                CommandHandler\SendEmailCommandHandler::class => CommandHandler\Container\SendEmailCommandHandlerFactory::class,
                Mailer::class                                 => Container\MailerFactory::class,
                Http\Middleware\MailerMiddleware::class       => Http\Middleware\Container\MailerMiddlewareFactory::class,
            ],
        ];
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
            'dependencies'             => $this->getDependencies(),
            'templates'                => $this->getTemplates(),
            MessageBusInterface::class => [
                BusProvider::COMMAND_MAP_KEY => $this->getCommandMap(),
            ],
            AdapterInterface::class    => $this->getAdapterConfig(),
        ];
    }
}
