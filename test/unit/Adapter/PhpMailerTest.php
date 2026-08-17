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

namespace WebwareTest\Mailer\Adapter;

use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer as BaseMailer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Mailer\Adapter\PhpMailer;

#[CoversClass(PhpMailer::class)]
#[CoversMethod(PhpMailer::class, 'addHeader')]
#[CoversMethod(PhpMailer::class, 'altBody')]
#[CoversMethod(PhpMailer::class, 'attach')]
#[CoversMethod(PhpMailer::class, 'attachFromString')]
#[CoversMethod(PhpMailer::class, 'bcc')]
#[CoversMethod(PhpMailer::class, 'body')]
#[CoversMethod(PhpMailer::class, 'cc')]
#[CoversMethod(PhpMailer::class, 'charset')]
#[CoversMethod(PhpMailer::class, 'encoding')]
#[CoversMethod(PhpMailer::class, 'from')]
#[CoversMethod(PhpMailer::class, 'isHtml')]
#[CoversMethod(PhpMailer::class, 'isMail')]
#[CoversMethod(PhpMailer::class, 'isSmtp')]
#[CoversMethod(PhpMailer::class, 'replyTo')]
#[CoversMethod(PhpMailer::class, 'reset')]
#[CoversMethod(PhpMailer::class, 'send')]
#[CoversMethod(PhpMailer::class, 'subject')]
#[CoversMethod(PhpMailer::class, 'to')]
final class PhpMailerTest extends TestCase
{
    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function addHeaderDelegatesToAddCustomHeader(): void
    {
        $base = $this->createMock(BaseMailer::class);
        $base->expects($this->once())->method('addCustomHeader')->with('X-Test', 'value');

        $adapter = new PhpMailer($base);

        $this->assertSame($adapter, $adapter->addHeader('X-Test', 'value'));
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function altBodySetsAltBodyProperty(): void
    {
        $base = new BaseMailer();
        $adapter = new PhpMailer($base);

        $this->assertSame($adapter, $adapter->altBody('plain text'));
        $this->assertSame('plain text', $base->AltBody);
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function attachDelegatesToAddAttachment(): void
    {
        $base = $this->createMock(BaseMailer::class);
        $base->expects($this->once())
            ->method('addAttachment')
            ->with('/tmp/file.txt', 'file.txt', 'base64', 'text/plain');

        $adapter = new PhpMailer($base);

        $this->assertSame($adapter, $adapter->attach('/tmp/file.txt', 'file.txt', 'text/plain'));
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function attachFromStringDelegatesToAddStringAttachment(): void
    {
        $base = $this->createMock(BaseMailer::class);
        $base->expects($this->once())
            ->method('addStringAttachment')
            ->with('content', 'file.txt', 'base64', 'text/plain');

        $adapter = new PhpMailer($base);

        $this->assertSame($adapter, $adapter->attachFromString('content', 'file.txt', 'text/plain'));
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function bccDelegatesToAddBCC(): void
    {
        $base = $this->createMock(BaseMailer::class);
        $base->expects($this->once())->method('addBCC')->with('bcc@example.com', '');

        $adapter = new PhpMailer($base);

        $this->assertSame($adapter, $adapter->bcc('bcc@example.com'));
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function bodySetsBodyProperty(): void
    {
        $base = new BaseMailer();
        $adapter = new PhpMailer($base);

        $this->assertSame($adapter, $adapter->body('<p>html</p>'));
        $this->assertSame('<p>html</p>', $base->Body);
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function ccDelegatesToAddCC(): void
    {
        $base = $this->createMock(BaseMailer::class);
        $base->expects($this->once())->method('addCC')->with('cc@example.com', '');

        $adapter = new PhpMailer($base);

        $this->assertSame($adapter, $adapter->cc('cc@example.com'));
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function charsetSetsCharSetProperty(): void
    {
        $base = new BaseMailer();
        $adapter = new PhpMailer($base);

        $this->assertSame($adapter, $adapter->charset('iso-8859-1'));
        $this->assertSame('iso-8859-1', $base->CharSet);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function encodingSetsEncodingProperty(): void
    {
        $base = new BaseMailer();
        $adapter = new PhpMailer($base);

        $this->assertSame($adapter, $adapter->encoding('quoted-printable'));
        $this->assertSame('quoted-printable', $base->Encoding);
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function fromDelegatesToSetFrom(): void
    {
        $base = $this->createMock(BaseMailer::class);
        $base->expects($this->once())->method('setFrom')->with('from@example.com', '');

        $adapter = new PhpMailer($base);

        $this->assertSame($adapter, $adapter->from('from@example.com'));
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function isHtmlDelegatesToIsHTML(): void
    {
        $base = $this->createMock(BaseMailer::class);
        $base->expects($this->once())->method('isHTML')->with(true);

        $adapter = new PhpMailer($base);

        $this->assertSame($adapter, $adapter->isHtml(true));
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function isHtmlDefaultsToTrue(): void
    {
        $base = new BaseMailer();
        $adapter = new PhpMailer($base);

        $adapter->isHtml();

        $this->assertSame('text/html', $base->ContentType);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function isMailDelegatesToIsMail(): void
    {
        $base = $this->createMock(BaseMailer::class);
        $base->expects($this->once())->method('isMail');

        $adapter = new PhpMailer($base);

        $this->assertSame($adapter, $adapter->isMail());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function isSmtpDelegatesToIsSMTP(): void
    {
        $base = $this->createMock(BaseMailer::class);
        $base->expects($this->once())->method('isSMTP');

        $adapter = new PhpMailer($base);

        $this->assertSame($adapter, $adapter->isSmtp());
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function replyToDelegatesToAddReplyTo(): void
    {
        $base = $this->createMock(BaseMailer::class);
        $base->expects($this->once())->method('addReplyTo')->with('reply@example.com', '');

        $adapter = new PhpMailer($base);

        $this->assertSame($adapter, $adapter->replyTo('reply@example.com'));
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function resetClearsSubjectBodyAndAltBody(): void
    {
        $base = new BaseMailer();
        $base->Subject = 'subject';
        $base->Body = 'body';
        $base->AltBody = 'alt';

        $adapter = new PhpMailer($base);

        $this->assertSame($adapter, $adapter->reset());
        $this->assertSame('', $base->Subject);
        $this->assertSame('', $base->Body);
        $this->assertSame('', $base->AltBody);
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function resetClearsRecipientsAttachmentsAndHeaders(): void
    {
        $base = new BaseMailer();
        $base->addAddress('to@example.com');
        $base->addCC('cc@example.com');
        $base->addBCC('bcc@example.com');
        $base->addReplyTo('reply@example.com');
        $base->addStringAttachment('content', 'file.txt');
        $base->addCustomHeader('X-Test', 'value');

        $adapter = new PhpMailer($base);
        $adapter->reset();

        $this->assertSame([], $base->getToAddresses());
        $this->assertSame([], $base->getCcAddresses());
        $this->assertSame([], $base->getBccAddresses());
        $this->assertSame([], $base->getReplyToAddresses());
        $this->assertSame([], $base->getAttachments());
        $this->assertSame([], $base->getCustomHeaders());
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function sendDelegatesToBaseMailerSend(): void
    {
        $base = $this->createMock(BaseMailer::class);
        $base->expects($this->once())->method('send')->willReturn(true);

        $adapter = new PhpMailer($base);

        $this->assertTrue($adapter->send());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function subjectSetsSubjectProperty(): void
    {
        $base = new BaseMailer();
        $adapter = new PhpMailer($base);

        $this->assertSame($adapter, $adapter->subject('Hello'));
        $this->assertSame('Hello', $base->Subject);
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function toDelegatesToAddAddress(): void
    {
        $base = $this->createMock(BaseMailer::class);
        $base->expects($this->once())->method('addAddress')->with('to@example.com', '');

        $adapter = new PhpMailer($base);

        $this->assertSame($adapter, $adapter->to('to@example.com'));
    }
}
