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

namespace WebwareTest\Mailer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\Mailer;

#[CoversClass(Mailer::class)]
#[CoversMethod(Mailer::class, 'getAdapter')]
#[CoversMethod(Mailer::class, 'send')]
#[CoversMethod(Mailer::class, 'setAdapter')]
final class MailerTest extends TestCase
{
    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function getAdapterReturnsNullByDefault(): void
    {
        $mailer = new Mailer(null);

        $this->assertNull($mailer->getAdapter());
    }

    /**
     * @throws \RuntimeException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function sendDelegatesToAdapter(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())->method('send')->willReturn(true);

        $mailer = new Mailer($adapter);

        $this->assertTrue($mailer->send());
    }

    /**
     * @throws \RuntimeException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function sendThrowsWithoutAdapter(): void
    {
        $mailer = new Mailer(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('No adapter configured on Mailer instance.');

        $mailer->send();
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function setAdapterUpdatesAdapterAndReturnsSelf(): void
    {
        $mailer  = new Mailer(null);
        $adapter = $this->createStub(AdapterInterface::class);

        $this->assertSame($mailer, $mailer->setAdapter($adapter));
        $this->assertSame($adapter, $mailer->getAdapter());
    }
}
