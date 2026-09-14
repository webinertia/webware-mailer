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
use Webware\Mailer\Adapter\PhpMailer;
use Webware\MessageBus\ConfigProvider as BusProvider;
use Webware\MessageBus\MessageBusInterface;

/**
 * @import-type PhpMailerConfig from PhpMailer
 */
final readonly class ConfigProvider
{
    /** @return PhpMailerConfig */
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

    /**
     * Both adapters are registered as services, but `AdapterInterface` is not
     * aliased here: mapping the contract to the implementation to use is the
     * host's runtime decision, so it is the one line the host adds. See
     * docs/v1/adapters.md.
     *
     * @return array<string, mixed>
     */
    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                MailerInterface::class => Mailer::class,
            ],
            'factories' => [
                CommandHandler\SendEmailCommandHandler::class => CommandHandler\Container\SendEmailCommandHandlerFactory::class,
                Mailer::class                                 => Container\MailerFactory::class,
                Http\Middleware\MailerMiddleware::class       => Http\Middleware\Container\MailerMiddlewareFactory::class,
                Adapter\PhpMailer::class                      => Container\PhpMailerFactory::class,
                Adapter\SymfonyMailer::class                  => Container\SymfonyMailerFactory::class,
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
