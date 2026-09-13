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

use function array_key_exists;
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

        $mailer = new BaseMailer($enableExceptions);

        if ($adapterConfig['useSmtp'] ?? false) {
            $mailer->isSMTP();
        }

        // Only keys the merged config actually provides are applied; anything
        // absent keeps PHPMailer's own default rather than a value mailer
        // invented for the host.
        if (array_key_exists('host', $adapterConfig)) {
            $mailer->Host = $adapterConfig['host'];
        }

        if (array_key_exists('port', $adapterConfig)) {
            $mailer->Port = $adapterConfig['port'];
        }

        if (array_key_exists('smtpAuth', $adapterConfig)) {
            $mailer->SMTPAuth = $adapterConfig['smtpAuth'];
        }

        if (array_key_exists('username', $adapterConfig)) {
            $mailer->Username = $adapterConfig['username'];
        }

        if (array_key_exists('password', $adapterConfig)) {
            $mailer->Password = $adapterConfig['password'];
        }

        if (array_key_exists('smtpSecure', $adapterConfig)) {
            $mailer->SMTPSecure = $adapterConfig['smtpSecure'];
        }

        if (array_key_exists('charset', $adapterConfig)) {
            $mailer->CharSet = $adapterConfig['charset'];
        }

        if (array_key_exists('encoding', $adapterConfig)) {
            $mailer->Encoding = $adapterConfig['encoding'];
        }

        if (array_key_exists('timeout', $adapterConfig)) {
            $mailer->Timeout = $adapterConfig['timeout'];
        }

        if (array_key_exists('from', $adapterConfig)) {
            $mailer->setFrom($adapterConfig['from'], $adapterConfig['fromName'] ?? '');
        }

        // The adapter reports the transport's effective state, not the config it
        // was handed — so consumers read what the mailer will actually use.
        return new PhpMailer(
            mailer          : $mailer,
            enableExceptions: $enableExceptions,
            charset         : $mailer->CharSet,
            encoding        : $mailer->Encoding,
            from            : $mailer->From,
            fromName        : $mailer->FromName,
            host            : $mailer->Host,
            password        : $mailer->Password,
            port            : $mailer->Port,
            smtpAuth        : $mailer->SMTPAuth,
            smtpSecure      : $mailer->SMTPSecure,
            timeout         : $mailer->Timeout,
            username        : $mailer->Username,
            useSmtp         : $adapterConfig['useSmtp'] ?? false,
        );
    }
}
