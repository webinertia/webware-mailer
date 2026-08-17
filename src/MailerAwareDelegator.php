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

use Psr\Container\ContainerInterface;

final class MailerAwareDelegator
{
    public function __invoke(ContainerInterface $container, string $serviceName, callable $callback): mixed
    {
        $service = $callback();
        if (!$service instanceof MailerAwareInterface) {
            return $service;
        }

        /** @var MailerInterface $mailer */
        $mailer = $container->get(MailerInterface::class);
        $service->setMailer($mailer);

        return $service;
    }
}
