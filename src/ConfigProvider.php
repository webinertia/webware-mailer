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
 * Keys match the adapter's constructor parameter names.
 *
 * @type AdapterConfig = array{
 *   enableExceptions?: bool,
 *   useSmtp?: bool,
 *   host?: non-empty-string,
 *   port?: int<1, 65535>,
 *   smtpAuth?: bool,
 *   username?: string,
 *   password?: string,
 *   smtpSecure?: ''|'tls'|'ssl',
 *   charset?: non-empty-string,
 *   encoding?: non-empty-string,
 *   timeout?: positive-int,
 *   from?: non-empty-string,
 *   fromName?: non-empty-string,
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
