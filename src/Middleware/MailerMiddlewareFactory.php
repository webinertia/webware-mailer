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

namespace Webware\Mailer\Middleware;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\ConfigProvider;
use Webware\Mailer\MailerInterface;

use function is_array;

final class MailerMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     */
    public function __invoke(ContainerInterface $container): MailerMiddleware
    {
        /** @var array<string, mixed> $appConfig */
        $appConfig = $container->get('config');

        if (! is_array($appConfig[ConfigProvider::class] ?? null)) {
            throw new RuntimeException('Service: ' . ConfigProvider::class . ' configuration must be an array.');
        }

        $mailerConfig = $appConfig[ConfigProvider::class];

        if (! is_array($mailerConfig[AdapterInterface::class] ?? null)) {
            throw new RuntimeException('Service: ' . AdapterInterface::class . ' configuration must be an array.');
        }

        $mailSettings = $mailerConfig[AdapterInterface::class];

        $mailer = $container->get(MailerInterface::class);

        return new MailerMiddleware($mailer, $mailSettings);
    }
}
