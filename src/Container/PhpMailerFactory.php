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
use PHPMailer\PHPMailer\PHPMailer as BaseMailer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\Adapter\PhpMailer;
use Webware\Mailer\ConfigProvider;

use function is_array;

/**
 * @import-type AdapterConfig from ConfigProvider
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

        /** @var AdapterConfig $adapterConfig */
        $adapterConfig = $candidate;

        $enableExceptions = $adapterConfig['enableExceptions'] ?? true;
        $useSmtp          = $adapterConfig['useSmtp'] ?? false;
        $from             = $adapterConfig['from'] ?? '';

        $mailer = new BaseMailer($enableExceptions);

        if ($useSmtp) {
            $mailer->isSMTP();
            $mailer->Host       = $adapterConfig['host'] ?? '';
            $mailer->Port       = $adapterConfig['port'] ?? 25;
            $mailer->SMTPAuth   = $adapterConfig['smtp_auth'] ?? false;
            $mailer->Username   = $adapterConfig['username'] ?? '';
            $mailer->Password   = $adapterConfig['password'] ?? '';
            $mailer->SMTPSecure = $adapterConfig['smtp_secure'] ?? '';
        }

        $mailer->CharSet  = $adapterConfig['charset'] ?? 'UTF-8';
        $mailer->Encoding = $adapterConfig['encoding'] ?? 'base64';
        $mailer->Timeout  = $adapterConfig['timeout'] ?? 30;

        if ('' !== $from) {
            $mailer->setFrom($from);
        }

        return new PhpMailer(
            mailer          : $mailer,
            enableExceptions: $enableExceptions,
            charset         : $mailer->CharSet,
            encoding        : $mailer->Encoding,
            from            : $from,
            host            : $adapterConfig['host'] ?? '',
            password        : $adapterConfig['password'] ?? '',
            port            : $adapterConfig['port'] ?? 25,
            smtpAuth        : $adapterConfig['smtp_auth'] ?? false,
            smtpSecure      : $adapterConfig['smtp_secure'] ?? '',
            timeout         : $mailer->Timeout,
            username        : $adapterConfig['username'] ?? '',
            useSmtp         : $useSmtp,
        );
    }
}
