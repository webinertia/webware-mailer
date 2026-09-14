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
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\Mailer;
use Webware\Mailer\Message;

/**
 * The mailer holds a transport and nothing else: the message arrives per send, so
 * there is no state here that could carry between sends.
 */
#[CoversClass(Mailer::class)]
#[CoversMethod(Mailer::class, 'getAdapter')]
#[CoversMethod(Mailer::class, 'send')]
final class MailerTest extends TestCase
{
    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function getAdapterReturnsTheInjectedAdapter(): void
    {
        $adapter = $this->createStub(AdapterInterface::class);

        $this->assertSame($adapter, new Mailer($adapter)->getAdapter());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function sendHandsTheMessageToTheAdapter(): void
    {
        $message = new Message();

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())->method('send')->with($message)->willReturn(true);

        $this->assertTrue(new Mailer($adapter)->send($message));
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function sendReportsWhatTheAdapterReturned(): void
    {
        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('send')->willReturn(false);

        $this->assertFalse(new Mailer($adapter)->send(new Message()));
    }

    /**
     * Two sends share the transport but not the message: nothing about the first
     * is visible to the second.
     *
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function sendsDoNotShareMessageState(): void
    {
        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('send')->willReturn(true);

        $mailer = new Mailer($adapter);

        $first  = new Message(to: [['alice@example.com', '']]);
        $second = new Message(to: [['bob@example.com', '']]);

        $this->assertTrue($mailer->send($first));
        $this->assertTrue($mailer->send($second));

        $this->assertSame([['alice@example.com', '']], $first->to);
        $this->assertSame([['bob@example.com', '']], $second->to);
    }
}
