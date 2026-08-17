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

use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

final class MailerAwareDelegator implements DelegatorFactoryInterface
{
    /**
     * @param callable(): mixed $callback
     * @param array<array-key, mixed>|null $options
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        string $name,
        callable $callback,
        ?array $options = null,
    ): mixed {
        /** @var object $service */
        $service = $callback();
        if (!$service instanceof MailerAwareInterface) {
            return $service;
        }

        $mailer = $container->get(MailerInterface::class);
        if (!$mailer instanceof Mailer) {
            throw new RuntimeException(
                'Delegator for MailerAwareInterface services requires a Webware\Mailer\Mailer instance.',
            );
        }

        $service->setMailer($mailer);

        return $service;
    }
}
