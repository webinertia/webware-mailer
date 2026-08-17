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

namespace WebwareTestIntegration\Mailer;

use JsonException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Override;
use PHPMailer\PHPMailer\Exception as MailerException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Symfony\Component\Process\Process;
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\Adapter\PhpMailer;
use Webware\Mailer\Container\PhpMailerFactory;
use Webware\Mailer\Mailer;

use function array_key_exists;
use function fclose;
use function is_string;
use function json_decode;
use function str_contains;
use function stream_socket_get_name;
use function stream_socket_server;
use function strrpos;
use function substr;

use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

#[CoversClass(PhpMailerFactory::class)]
#[CoversMethod(PhpMailerFactory::class, '__invoke')]
#[CoversClass(PhpMailer::class)]
#[CoversMethod(PhpMailer::class, 'to')]
#[CoversMethod(PhpMailer::class, 'from')]
#[CoversMethod(PhpMailer::class, 'subject')]
#[CoversMethod(PhpMailer::class, 'body')]
#[CoversMethod(PhpMailer::class, 'send')]
#[CoversClass(Mailer::class)]
#[CoversMethod(Mailer::class, 'send')]
final class SmtpSendIntegrationTest extends TestCase
{
    private ?Process $process = null;

    /**
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws MailerException
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function sendsMailThroughSmtp(): void
    {
        $port = $this->freePort();
        $this->startServer($port);

        $adapter = $this->adapter([
            'useSmtp' => true,
            'enableExceptions' => true,
            'host' => '127.0.0.1',
            'port' => $port,
            'smtp_auth' => false,
        ]);

        $adapter->to('to@example.com')
            ->from('from@example.com')
            ->subject('Integration Subject')
            ->body('Integration Body');

        $mailer = new Mailer($adapter);

        self::assertTrue($mailer->send());

        $data = $this->serverOutput();

        self::assertStringContainsString('To: to@example.com', $data);
        self::assertStringContainsString('From: from@example.com', $data);
        self::assertStringContainsString('Subject: Integration Subject', $data);
        self::assertStringContainsString('SW50ZWdyYXRpb24gQm9keQ==', $data);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws MailerException
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function sendThrowsWhenSmtpUnreachable(): void
    {
        $adapter = $this->adapter([
            'useSmtp' => true,
            'enableExceptions' => true,
            'host' => '127.0.0.1',
            'port' => $this->freePort(),
            'smtp_auth' => false,
            'timeout' => 2,
        ]);

        $mailer = new Mailer($adapter);

        $this->expectException(MailerException::class);

        $mailer->send();
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->process?->stop();
        $this->process = null;
    }

    /**
     * @param array<string, mixed> $smtp
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ServiceNotCreatedException
     */
    private function adapter(array $smtp): AdapterInterface
    {
        $container = new class(['config' => [AdapterInterface::class => $smtp]]) implements ContainerInterface {
            /**
             * @param array<string, mixed> $config
             */
            public function __construct(
                private array $config,
            ) {}

            #[Override]
            public function get(string $id): mixed
            {
                return $this->config[$id] ?? null;
            }

            #[Override]
            public function has(string $id): bool
            {
                return array_key_exists($id, $this->config);
            }
        };

        return (new PhpMailerFactory())($container);
    }

    /**
     * @throws RuntimeException
     */
    private function startServer(int $port): void
    {
        $process = new Process([PHP_BINARY, __DIR__ . '/TestAsset/fake-smtp-server.php', (string) $port]);
        $process->start();
        $this->process = $process;

        $process->waitUntil(static fn(string $type, string $output): bool => str_contains($type . $output, 'READY'));
    }

    /**
     * @throws JsonException
     * @throws RuntimeException
     */
    private function serverOutput(): string
    {
        $process = $this->requireProcess();
        $process->wait();

        $stdout = $process->getOutput();
        $position = strrpos(haystack: $stdout, needle: '{');

        if (false === $position) {
            throw new RuntimeException('Unexpected SMTP server output.');
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode(substr($stdout, $position), associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);

        if (!is_string($payload['data'] ?? null)) {
            throw new RuntimeException('Unexpected SMTP server output.');
        }

        return $payload['data'];
    }

    /**
     * @throws RuntimeException
     */
    private function requireProcess(): Process
    {
        if (null === $this->process) {
            throw new RuntimeException('No fake SMTP server running.');
        }

        return $this->process;
    }

    /**
     * @throws RuntimeException
     */
    private function freePort(): int
    {
        $errno = null;
        $errstr = null;
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if (false === $socket) {
            throw new RuntimeException('Unable to allocate a free port (' . ($errno ?? 0) . '): ' . ($errstr ?? ''));
        }

        $address = stream_socket_get_name($socket, remote: false);
        fclose($socket);

        $position = strrpos(haystack: (string) $address, needle: ':');
        if (false === $position) {
            throw new RuntimeException('Unexpected socket address.');
        }

        return (int) substr((string) $address, $position + 1);
    }
}
