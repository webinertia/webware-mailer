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
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\Adapter\SymfonyMailer;
use Webware\Mailer\Container\SymfonyMailerFactory;

use function array_key_exists;

#[CoversClass(SymfonyMailerFactory::class)]
#[CoversMethod(SymfonyMailerFactory::class, '__invoke')]
final class SymfonyMailerFactoryTest extends TestCase
{
    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeBuildsTheAdapterFromTheAdapterSection(): void
    {
        $factory = new SymfonyMailerFactory();

        /** @var SymfonyMailer $adapter */
        $adapter = $factory($this->container([
            AdapterInterface::class => [
                'transport' => 'null',
            ],
        ]));

        $this->assertInstanceOf(SymfonyMailer::class, $adapter);
        $this->assertSame('null', $adapter->transport);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeThrowsWhenTheAdapterSectionIsEmpty(): void
    {
        $factory = new SymfonyMailerFactory();

        $this->expectException(ServiceNotCreatedException::class);

        $factory($this->container([AdapterInterface::class => []]));
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function invokeThrowsWhenTheAdapterSectionIsMissing(): void
    {
        $factory = new SymfonyMailerFactory();

        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage(
            'Service: ' . SymfonyMailer::class . ' could not be created. Missing configuration.',
        );

        $factory($this->container([]));
    }

    /**
     * The factory reads the `config` service and then the adapter section inside
     * it, so the container is built with that nesting.
     *
     * @param array<string, mixed> $config
     */
    private function container(array $config): ContainerInterface
    {
        return new class(['config' => $config]) implements ContainerInterface {
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
    }
}
