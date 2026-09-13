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
use ReflectionProperty;
use Webware\Mailer\Adapter\PhpMailer;

use function bin2hex;
use function file_put_contents;
use function random_bytes;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

#[CoversClass(PhpMailer::class)]
#[CoversMethod(PhpMailer::class, '__construct')]
#[CoversMethod(PhpMailer::class, 'isMail')]
#[CoversMethod(PhpMailer::class, 'isSmtp')]
#[CoversMethod(PhpMailer::class, 'send')]
#[CoversMethod(PhpMailer::class, 'withAltBody')]
#[CoversMethod(PhpMailer::class, 'withAttachment')]
#[CoversMethod(PhpMailer::class, 'withAttachmentFromString')]
#[CoversMethod(PhpMailer::class, 'withBcc')]
#[CoversMethod(PhpMailer::class, 'withBody')]
#[CoversMethod(PhpMailer::class, 'withCc')]
#[CoversMethod(PhpMailer::class, 'withCharset')]
#[CoversMethod(PhpMailer::class, 'withEncoding')]
#[CoversMethod(PhpMailer::class, 'withFrom')]
#[CoversMethod(PhpMailer::class, 'withHeader')]
#[CoversMethod(PhpMailer::class, 'withHtml')]
#[CoversMethod(PhpMailer::class, 'withReplyTo')]
#[CoversMethod(PhpMailer::class, 'withSubject')]
#[CoversMethod(PhpMailer::class, 'withTo')]
final class PhpMailerTest extends TestCase
{
    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function constructorExposesTheConfigurationContract(): void
    {
        $adapter = new PhpMailer(
            mailer          : new BaseMailer(),
            enableExceptions: false,
            charset         : 'iso-8859-1',
            encoding        : 'quoted-printable',
            from            : 'from@example.com',
            host            : 'smtp.example.com',
            password        : bin2hex(random_bytes(16)),
            port            : 587,
            smtpAuth        : true,
            smtpSecure      : 'tls',
            timeout         : 45,
            username        : 'user',
            useSmtp         : true,
        );

        $this->assertFalse($adapter->enableExceptions);
        $this->assertSame('iso-8859-1', $adapter->charset);
        $this->assertSame('quoted-printable', $adapter->encoding);
        $this->assertSame('from@example.com', $adapter->from);
        $this->assertSame('smtp.example.com', $adapter->host);
        $this->assertNotSame('', $adapter->password);
        $this->assertSame(587, $adapter->port);
        $this->assertTrue($adapter->smtpAuth);
        $this->assertSame('tls', $adapter->smtpSecure);
        $this->assertSame(45, $adapter->timeout);
        $this->assertSame('user', $adapter->username);
        $this->assertTrue($adapter->useSmtp);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function defaultsAreAppliedWhenOnlyTheTransportIsGiven(): void
    {
        $adapter = new PhpMailer(new BaseMailer());

        $this->assertTrue($adapter->enableExceptions);
        $this->assertSame('UTF-8', $adapter->charset);
        $this->assertSame('base64', $adapter->encoding);
        $this->assertSame('', $adapter->from);
        $this->assertSame('', $adapter->host);
        $this->assertSame('', $adapter->password);
        $this->assertSame(25, $adapter->port);
        $this->assertFalse($adapter->smtpAuth);
        $this->assertSame('', $adapter->smtpSecure);
        $this->assertSame(30, $adapter->timeout);
        $this->assertSame('', $adapter->username);
        $this->assertFalse($adapter->useSmtp);
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
    public function sendDelegatesToBaseMailerSend(): void
    {
        $base = $this->createMock(BaseMailer::class);
        $base->expects($this->once())->method('send')->willReturn(true);

        $adapter = new PhpMailer($base);

        $this->assertTrue($adapter->send());
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withAltBodyReturnsNewInstanceAndSetsAltBody(): void
    {
        $adapter = new PhpMailer(new BaseMailer());

        $next = $adapter->withAltBody('plain text');

        $this->assertNotSame($adapter, $next);
        $this->assertSame('plain text', $this->transport($next)->AltBody);
        $this->assertSame('', $this->transport($adapter)->AltBody);
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withAttachmentDelegatesToAddAttachment(): void
    {
        $path = (string) tempnam(
            directory: sys_get_temp_dir(),
            prefix   : 'mailer-attach',
        );
        file_put_contents(
            filename: $path,
            data    : 'content',
        );

        $adapter = new PhpMailer(new BaseMailer());
        $next    = $adapter->withAttachment($path, 'file.txt', 'text/plain');

        $this->assertNotSame($adapter, $next);
        $this->assertCount(1, $this->transport($next)->getAttachments());
        $this->assertSame([], $this->transport($adapter)->getAttachments());

        unlink(filename: $path);
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withAttachmentFromStringDelegatesToAddStringAttachment(): void
    {
        $adapter = new PhpMailer(new BaseMailer());

        $next = $adapter->withAttachmentFromString('content', 'file.txt', 'text/plain');

        $this->assertNotSame($adapter, $next);
        $this->assertCount(1, $this->transport($next)->getAttachments());
        $this->assertSame([], $this->transport($adapter)->getAttachments());
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withBccAddsBccAddress(): void
    {
        $adapter = new PhpMailer(new BaseMailer());

        $next = $adapter->withBcc('bcc@example.com', 'Bcc');

        $this->assertNotSame($adapter, $next);
        $this->assertSame('bcc@example.com', $this->transport($next)->getBccAddresses()[0][0]);
        $this->assertSame([], $this->transport($adapter)->getBccAddresses());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withBodyReturnsNewInstanceAndSetsBody(): void
    {
        $adapter = new PhpMailer(new BaseMailer());

        $next = $adapter->withBody('<p>html</p>');

        $this->assertNotSame($adapter, $next);
        $this->assertSame('<p>html</p>', $this->transport($next)->Body);
        $this->assertSame('', $this->transport($adapter)->Body);
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withCcAddsCcAddress(): void
    {
        $adapter = new PhpMailer(new BaseMailer());

        $next = $adapter->withCc('cc@example.com', 'Cc');

        $this->assertNotSame($adapter, $next);
        $this->assertSame('cc@example.com', $this->transport($next)->getCcAddresses()[0][0]);
        $this->assertSame([], $this->transport($adapter)->getCcAddresses());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withCharsetReturnsNewInstanceAndSetsCharSet(): void
    {
        $adapter = new PhpMailer(new BaseMailer());

        $unchanged = $this->transport($adapter)->CharSet;
        $next      = $adapter->withCharset('iso-8859-1');

        $this->assertNotSame($adapter, $next);
        $this->assertSame('iso-8859-1', $this->transport($next)->CharSet);
        $this->assertSame($unchanged, $this->transport($adapter)->CharSet);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withEncodingReturnsNewInstanceAndSetsEncoding(): void
    {
        $adapter = new PhpMailer(new BaseMailer());

        $unchanged = $this->transport($adapter)->Encoding;
        $next      = $adapter->withEncoding('quoted-printable');

        $this->assertNotSame($adapter, $next);
        $this->assertSame('quoted-printable', $this->transport($next)->Encoding);
        $this->assertSame($unchanged, $this->transport($adapter)->Encoding);
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withFromDelegatesToSetFrom(): void
    {
        $adapter = new PhpMailer(new BaseMailer());

        $next = $adapter->withFrom('from@example.com', 'From');

        $this->assertNotSame($adapter, $next);
        $this->assertSame('from@example.com', $this->transport($next)->From);
        $this->assertSame('', $this->transport($adapter)->From);
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withHeaderDelegatesToAddCustomHeader(): void
    {
        $adapter = new PhpMailer(new BaseMailer());

        $next = $adapter->withHeader('X-Test', 'value');

        $this->assertNotSame($adapter, $next);
        $this->assertSame([['X-Test', 'value']], $this->transport($next)->getCustomHeaders());
        $this->assertSame([], $this->transport($adapter)->getCustomHeaders());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withHtmlDefaultsToTrue(): void
    {
        $adapter = new PhpMailer(new BaseMailer());

        $unchanged = $this->transport($adapter)->ContentType;
        $next      = $adapter->withHtml();

        $this->assertNotSame($adapter, $next);
        $this->assertSame('text/html', $this->transport($next)->ContentType);
        $this->assertSame($unchanged, $this->transport($adapter)->ContentType);
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withReplyToDelegatesToAddReplyTo(): void
    {
        $adapter = new PhpMailer(new BaseMailer());

        $next = $adapter->withReplyTo('reply@example.com', 'Reply');

        $this->assertNotSame($adapter, $next);
        $this->assertCount(1, $this->transport($next)->getReplyToAddresses());
        $this->assertSame([], $this->transport($adapter)->getReplyToAddresses());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withSubjectReturnsNewInstanceAndSetsSubject(): void
    {
        $adapter = new PhpMailer(new BaseMailer());

        $next = $adapter->withSubject('Hello');

        $this->assertNotSame($adapter, $next);
        $this->assertSame('Hello', $this->transport($next)->Subject);
        $this->assertSame('', $this->transport($adapter)->Subject);
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withToDelegatesToAddAddress(): void
    {
        $adapter = new PhpMailer(new BaseMailer());

        $next = $adapter->withTo('to@example.com', 'To');

        $this->assertNotSame($adapter, $next);
        $this->assertSame('to@example.com', $this->transport($next)->getToAddresses()[0][0]);
        $this->assertSame([], $this->transport($adapter)->getToAddresses());
    }

    private function transport(PhpMailer $adapter): BaseMailer
    {
        return new ReflectionProperty(PhpMailer::class, 'mailer')->getValue($adapter);
    }
}
