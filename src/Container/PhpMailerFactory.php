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

namespace Webware\Mailer\Container;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use PHPMailer\PHPMailer\Exception as MailerException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\Adapter\PhpMailer;

use function is_array;

/**
 * @import-type PhpMailerConfig from PhpMailer
 */
final class PhpMailerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws MailerException
     * @throws NotFoundExceptionInterface
     * @throws ServiceNotCreatedException
     */
    public function __invoke(ContainerInterface $container): AdapterInterface&PhpMailer
    {
        /** @var array<string, mixed> $appConfig */
        $appConfig = $container->get('config');

        /** @var mixed $candidate */
        $candidate = $appConfig[AdapterInterface::class] ?? null;

        if (! is_array($candidate) || [] === $candidate) {
            throw new ServiceNotCreatedException(
                'Service: ' . PhpMailer::class . ' could not be created. Missing configuration.',
            );
        }

        /** @var PhpMailerConfig $adapterConfig */
        $adapterConfig = $candidate;

        return PhpMailer::fromConfig($adapterConfig);
    }
}
