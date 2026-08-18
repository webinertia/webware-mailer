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

namespace WebwareTest\Mailer\Container;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use LogicException;
use PHPMailer\PHPMailer\PHPMailer as BaseMailer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use ReflectionProperty;
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\Adapter\PhpMailer;
use Webware\Mailer\Container\PhpMailerFactory;

use function is_bool;

#[CoversClass(PhpMailerFactory::class)]
#[CoversMethod(PhpMailerFactory::class, '__invoke')]
final class PhpMailerFactoryTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeAppliesSmtpDefaultsWhenKeysMissing(): void
    {
        $factory = new PhpMailerFactory();
        $result  = $factory($this->makeContainer([
            AdapterInterface::class => [
                'useSmtp' => true,
                'host'    => 'smtp.example.com',
            ],
        ]));

        $base = $this->baseMailer($result);

        $this->assertSame('smtp', $base->Mailer);
        $this->assertSame(25, $base->Port);
        $this->assertFalse($base->SMTPAuth);
        $this->assertSame('UTF-8', $base->CharSet);
        $this->assertSame('base64', $base->Encoding);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeConfiguresSmtpWhenEnabled(): void
    {
        $factory = new PhpMailerFactory();
        $result  = $factory($this->makeContainer([
            AdapterInterface::class => [
                'useSmtp'          => true,
                'enableExceptions' => true,
                'host'             => 'smtp.example.com',
                'port'             => 587,
                'smtp_auth'        => true,
                'username'         => 'user',
                // @mago-expect lint:no-literal-password
                'password'    => 'pass',
                'smtp_secure' => 'tls',
            ],
        ]));

        $base = $this->baseMailer($result);

        $this->assertSame('smtp.example.com', $base->Host);
        $this->assertSame(587, $base->Port);
        $this->assertTrue($base->SMTPAuth);
        $this->assertSame('user', $base->Username);
        $this->assertSame('pass', $base->Password);
        $this->assertSame('tls', $base->SMTPSecure);
        $this->assertSame(30, $base->Timeout);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeHonorsCharsetAndEncodingConfig(): void
    {
        $factory = new PhpMailerFactory();
        $result  = $factory($this->makeContainer([
            AdapterInterface::class => [
                'useSmtp'  => true,
                'host'     => 'smtp.example.com',
                'charset'  => 'iso-8859-1',
                'encoding' => 'quoted-printable',
            ],
        ]));

        $base = $this->baseMailer($result);

        $this->assertSame('iso-8859-1', $base->CharSet);
        $this->assertSame('quoted-printable', $base->Encoding);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeHonorsEnableExceptionsConfig(): void
    {
        $factory = new PhpMailerFactory();
        $result  = $factory($this->makeContainer([
            AdapterInterface::class => [
                'useSmtp'          => false,
                'enableExceptions' => false,
            ],
        ]));

        $this->assertFalse($this->exceptionsFlag($this->baseMailer($result)));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeHonorsTimeoutConfig(): void
    {
        $factory = new PhpMailerFactory();
        $result  = $factory($this->makeContainer([
            AdapterInterface::class => [
                'useSmtp' => true,
                'host'    => 'smtp.example.com',
                'timeout' => 45,
            ],
        ]));

        $this->assertSame(45, $this->baseMailer($result)->Timeout);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeReturnsPhpMailerWithDefaultExceptionsFlag(): void
    {
        $factory = new PhpMailerFactory();
        $result  = $factory($this->makeContainer([
            AdapterInterface::class => [
                'useSmtp' => false,
            ],
        ]));

        $this->assertInstanceOf(PhpMailer::class, $result);
        $this->assertTrue($this->exceptionsFlag($this->baseMailer($result)));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeSkipsSmtpWhenUseSmtpFlagMissing(): void
    {
        $factory = new PhpMailerFactory();
        $result  = $factory($this->makeContainer([
            AdapterInterface::class => [
                'host' => 'smtp.example.com',
            ],
        ]));

        $this->assertSame('localhost', $this->baseMailer($result)->Host);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeThrowsWhenAdapterConfigMissing(): void
    {
        $factory = new PhpMailerFactory();

        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessageIs(
            'Service: ' . PhpMailer::class . ' could not be created. Missing configuration.',
        );

        $factory($this->makeContainer([]));
    }

    /**
     * @throws ReflectionException
     * @throws \PHPUnit\Exception
     */
    private function baseMailer(PhpMailer $adapter): BaseMailer
    {
        $property = new ReflectionProperty(PhpMailer::class, 'mailer');

        /** @var mixed $base */
        $base = $property->getValue($adapter);

        if (! $base instanceof BaseMailer) {
            throw new LogicException('Expected a BaseMailer instance.');
        }

        return $base;
    }

    /**
     * @throws ReflectionException
     * @throws \PHPUnit\Exception
     */
    private function exceptionsFlag(BaseMailer $base): bool
    {
        $property = new ReflectionProperty(BaseMailer::class, 'exceptions');

        /** @var mixed $value */
        $value = $property->getValue($base);

        if (! is_bool($value)) {
            throw new LogicException('Expected a bool value.');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $config
     * @throws \PHPUnit\Exception
     */
    private function makeContainer(array $config): ContainerInterface
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(
                static fn(string $id): mixed => 'config' === $id ? $config : null,
            );

        return $container;
    }
}
