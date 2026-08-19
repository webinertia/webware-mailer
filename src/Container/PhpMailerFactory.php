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
use PHPMailer\PHPMailer\PHPMailer as BaseMailer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\Adapter\PhpMailer;

final class PhpMailerFactory
{
    /**
     * @param array<string, mixed> $adapterConfig
     */
    private function configureSmtp(BaseMailer $mailer, array $adapterConfig): void
    {
        $mailer->isSMTP();
        $mailer->Host       = (string) ($adapterConfig['host'] ?? '');
        $mailer->Port       = (int) ($adapterConfig['port'] ?? 25);
        $mailer->SMTPAuth   = ($adapterConfig['smtp_auth'] ?? false) === true;
        $mailer->Username   = (string) ($adapterConfig['username'] ?? '');
        $mailer->Password   = (string) ($adapterConfig['password'] ?? '');
        $mailer->CharSet    = (string) ($adapterConfig['charset'] ?? 'UTF-8');
        $mailer->Encoding   = (string) ($adapterConfig['encoding'] ?? 'base64');
        $mailer->Timeout    = (int) ($adapterConfig['timeout'] ?? 30);
        $mailer->SMTPSecure = (string) ($adapterConfig['smtp_secure'] ?? '');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ServiceNotCreatedException
     */
    public function __invoke(ContainerInterface $container): AdapterInterface&PhpMailer
    {
        /** @var array<string, mixed> $adapterConfig */
        $adapterConfig = $container->get('config')[AdapterInterface::class] ?? [];

        if ([] === $adapterConfig) {
            throw new ServiceNotCreatedException(
                'Service: ' . PhpMailer::class . ' could not be created. Missing configuration.',
            );
        }

        $mailer = new BaseMailer(($adapterConfig['enableExceptions'] ?? true) === true);

        if (($adapterConfig['useSmtp'] ?? false) === true) {
            $this->configureSmtp($mailer, $adapterConfig);
        }

        return new PhpMailer($mailer);
    }
}
