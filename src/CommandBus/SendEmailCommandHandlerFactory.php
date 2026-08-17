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

namespace Webware\Mailer\CommandBus;

use Psr\Container\ContainerInterface;
use Webware\Mailer\MailerInterface;

final class SendEmailCommandHandlerFactory
{
    public function __invoke(ContainerInterface $container): SendEmailCommandHandler
    {
        /** @var MailerInterface $mailer */
        $mailer = $container->get(MailerInterface::class);

        return new SendEmailCommandHandler($mailer);
    }
}
