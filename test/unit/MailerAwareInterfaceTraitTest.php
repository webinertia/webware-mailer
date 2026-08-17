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

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\Mailer;
use Webware\Mailer\MailerAwareInterface;
use Webware\Mailer\MailerAwareInterfaceTrait;
use Webware\Mailer\MailerInterface;

#[CoversTrait(MailerAwareInterfaceTrait::class)]
final class MailerAwareInterfaceTraitTest extends TestCase
{
    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function setAndGetMailerRoundTrip(): void
    {
        $aware = new class(new Mailer($this->createStub(AdapterInterface::class))) implements MailerAwareInterface {
            use MailerAwareInterfaceTrait;

            public function __construct(MailerInterface $mailer)
            {
                $this->mailerInterface = $mailer;
            }
        };

        $mailer = new Mailer($this->createStub(AdapterInterface::class));

        $aware->setMailer($mailer);

        $this->assertSame($mailer, $aware->getMailer());
    }
}
