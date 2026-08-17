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

use Psr\Container\ContainerInterface;
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\ConfigProvider;
use Webware\Mailer\MailerInterface;

class MailerMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): MailerMiddleware
    {
        /** @var array<string, mixed> $appConfig */
        $appConfig = $container->get('config');

        /** @var array<string, mixed> $mailSettings */
        $mailSettings = $appConfig[ConfigProvider::class][AdapterInterface::class];

        /** @var MailerInterface $mailer */
        $mailer = $container->get(MailerInterface::class);

        return new MailerMiddleware($mailer, $mailSettings);
    }
}
