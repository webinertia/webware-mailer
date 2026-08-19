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

namespace WebwareTest\Mailer\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use Webware\Mailer\Event\MessageEvent;

#[CoversClass(MessageEvent::class)]
final class MessageEventTest extends TestCase
{
    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function eventEmailMessageConstantIsDefined(): void
    {
        $this->assertSame('emailMessage', MessageEvent::EVENT_EMAIL_MESSAGE);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function explicitNameIsPreserved(): void
    {
        $event = new MessageEvent('custom');

        $this->assertSame('custom', $event->getName());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function nameDefaultsToClassWhenUnset(): void
    {
        $event = new MessageEvent();

        $this->assertSame(MessageEvent::class, $event->getName());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function paramsRoundTrip(): void
    {
        $event = new MessageEvent();

        $event->setParam('key', 'value');

        $this->assertSame('value', $event->getParam('key'));
        $this->assertSame(['key' => 'value'], $event->getParams());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function targetRoundTrips(): void
    {
        $event  = new MessageEvent();
        $target = new stdClass();

        $event->setTarget($target);

        $this->assertSame($target, $event->getTarget());
    }
}
