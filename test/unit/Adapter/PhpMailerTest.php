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

use function base64_encode;
use function bin2hex;
use function file_put_contents;
use function random_bytes;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

#[CoversClass(PhpMailer::class)]
#[CoversMethod(PhpMailer::class, '__construct')]
#[CoversMethod(PhpMailer::class, 'fromConfig')]
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
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function charsetFromConfigReachesTheTransport(): void
    {
        $adapter = PhpMailer::fromConfig(['charset' => 'utf-8']);

        $this->assertSame('utf-8', $adapter->charset);
        $this->assertSame('utf-8', $this->transport($adapter)->CharSet);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function defaultsMirrorTheTransportWithNoConfiguration(): void
    {
        $adapter = PhpMailer::fromConfig();

        $this->assertTrue($adapter->enableExceptions);
        $this->assertSame('iso-8859-1', $adapter->charset);
        $this->assertSame('8bit', $adapter->encoding);
        $this->assertSame('', $adapter->from);
        $this->assertSame('', $adapter->fromName);
        $this->assertSame('localhost', $adapter->host);
        $this->assertSame('', $adapter->password);
        $this->assertSame(25, $adapter->port);
        $this->assertFalse($adapter->smtpAuth);
        $this->assertSame('', $adapter->smtpSecure);
        $this->assertSame(300, $adapter->timeout);
        $this->assertSame('', $adapter->username);

        $this->assertSame('iso-8859-1', $this->transport($adapter)->CharSet);
        $this->assertSame('text/plain', $this->transport($adapter)->ContentType);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function fromConfigExposesTheConfigurationContract(): void
    {
        $adapter = PhpMailer::fromConfig([
            'enableExceptions' => false,
            'charset'          => 'iso-8859-1',
            'encoding'         => 'quoted-printable',
            'from'             => 'from@example.com',
            'fromName'         => 'Example Sender',
            'host'             => 'smtp.example.com',
            'password'         => bin2hex(random_bytes(16)),
            'port'             => 587,
            'smtpAuth'         => true,
            'smtpSecure'       => 'tls',
            'timeout'          => 45,
            'username'         => 'user',
        ]);

        $this->assertFalse($adapter->enableExceptions);
        $this->assertSame('iso-8859-1', $adapter->charset);
        $this->assertSame('quoted-printable', $adapter->encoding);
        $this->assertSame('from@example.com', $adapter->from);
        $this->assertSame('Example Sender', $adapter->fromName);
        $this->assertSame('smtp.example.com', $adapter->host);
        $this->assertNotSame('', $adapter->password);
        $this->assertSame(587, $adapter->port);
        $this->assertTrue($adapter->smtpAuth);
        $this->assertSame('tls', $adapter->smtpSecure);
        $this->assertSame(45, $adapter->timeout);
        $this->assertSame('user', $adapter->username);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function isMailReportsTheTransportMode(): void
    {
        $adapter = PhpMailer::fromConfig();

        $this->assertTrue($adapter->isMail());
        $this->assertFalse($adapter->isSmtp());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function isSmtpReportsTheTransportMode(): void
    {
        $adapter = PhpMailer::fromConfig(['useSmtp' => true]);

        $this->assertTrue($adapter->isSmtp());
        $this->assertFalse($adapter->isMail());
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function sendDelegatesToTheTransport(): void
    {
        $adapter = PhpMailer::fromConfig(['enableExceptions' => true]);

        $this->expectException(MailerException::class);

        $adapter->send();
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withAltBodyReturnsNewInstanceAndSetsAltBody(): void
    {
        $adapter = PhpMailer::fromConfig();

        $next = $adapter->withAltBody('plain text');

        $this->assertNotSame($adapter, $next);
        $this->assertSame('plain text', $this->transport($next)->AltBody);
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withAttachmentAccumulatesAttachmentsAcrossRebuilds(): void
    {
        $path = (string) tempnam(
            directory: sys_get_temp_dir(),
            prefix   : 'mailer-attach-accumulate',
        );
        file_put_contents(
            filename: $path,
            data    : 'content',
        );

        $adapter = PhpMailer::fromConfig()
            ->withAttachment($path, 'first.txt', 'text/plain')
            ->withAttachmentFromString('raw content', 'second.txt', 'text/plain');

        $this->assertCount(2, $this->transport($adapter)->getAttachments());

        unlink(filename: $path);
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

        $adapter = PhpMailer::fromConfig();
        $next    = $adapter->withTo('to@example.com')
            ->withBody('body')
            ->withAttachment($path, 'file.txt', 'text/plain');

        $this->assertNotSame($adapter, $next);
        $this->assertCount(1, $this->transport($next)->getAttachments());
        $this->assertStringContainsString(base64_encode('content'), $this->mime($next));

        unlink(filename: $path);
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withAttachmentFromStringDelegatesToAddStringAttachment(): void
    {
        $adapter = PhpMailer::fromConfig();

        $next = $adapter->withTo('to@example.com')
            ->withBody('body')
            ->withAttachmentFromString('content', 'file.txt', 'text/plain');

        $this->assertNotSame($adapter, $next);
        $this->assertCount(1, $this->transport($next)->getAttachments());
        $this->assertStringContainsString(base64_encode('content'), $this->mime($next));
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withAttachmentFromStringKeepsFollowingAttachments(): void
    {
        $path = (string) tempnam(
            directory: sys_get_temp_dir(),
            prefix   : 'mailer-attach-raw-first',
        );
        file_put_contents(
            filename: $path,
            data    : 'content',
        );

        $adapter = PhpMailer::fromConfig()
            ->withAttachmentFromString('raw content', 'first.txt', 'text/plain')
            ->withAttachment($path, 'second.txt', 'text/plain');

        $this->assertCount(2, $this->transport($adapter)->getAttachments());

        unlink(filename: $path);
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withBccAccumulatesRecipientsAcrossRebuilds(): void
    {
        $adapter = PhpMailer::fromConfig()
            ->withBcc('first@example.com', 'First')
            ->withBcc('second@example.com', 'Second');

        $this->assertSame(
            [
                ['first@example.com',  'First'],
                ['second@example.com', 'Second'],
            ],
            $this->transport($adapter)->getBccAddresses(),
        );
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withBccAddsBccAddress(): void
    {
        $adapter = PhpMailer::fromConfig();

        $next = $adapter->withBcc('bcc@example.com', 'Bcc');

        $this->assertNotSame($adapter, $next);
        $this->assertSame('bcc@example.com', $this->transport($next)->getBccAddresses()[0][0]);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withBodyReturnsNewInstanceAndSetsBody(): void
    {
        $adapter = PhpMailer::fromConfig();

        $next = $adapter->withBody('<p>html</p>');

        $this->assertNotSame($adapter, $next);
        $this->assertSame('<p>html</p>', $this->transport($next)->Body);
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withCcAccumulatesRecipientsAcrossRebuilds(): void
    {
        $adapter = PhpMailer::fromConfig()
            ->withCc('first@example.com', 'First')
            ->withCc('second@example.com', 'Second');

        $this->assertSame(
            [
                ['first@example.com',  'First'],
                ['second@example.com', 'Second'],
            ],
            $this->transport($adapter)->getCcAddresses(),
        );
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withCcAddsCcAddress(): void
    {
        $adapter = PhpMailer::fromConfig();

        $next = $adapter->withCc('cc@example.com', 'Cc');

        $this->assertNotSame($adapter, $next);
        $this->assertSame('cc@example.com', $this->transport($next)->getCcAddresses()[0][0]);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withCharsetReturnsNewInstanceAndSetsCharSet(): void
    {
        $adapter = PhpMailer::fromConfig();

        $next = $adapter->withCharset('iso-8859-1');

        $this->assertNotSame($adapter, $next);
        $this->assertSame('iso-8859-1', $this->transport($next)->CharSet);
        $this->assertSame('iso-8859-1', $next->charset);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withEncodingReturnsNewInstanceAndSetsEncoding(): void
    {
        $adapter = PhpMailer::fromConfig();

        $next = $adapter->withEncoding('quoted-printable');

        $this->assertNotSame($adapter, $next);
        $this->assertSame('quoted-printable', $this->transport($next)->Encoding);
        $this->assertSame('quoted-printable', $next->encoding);
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withFromDelegatesToSetFrom(): void
    {
        $adapter = PhpMailer::fromConfig();

        $next = $adapter->withFrom('from@example.com', 'From');

        $this->assertNotSame($adapter, $next);
        $this->assertSame('from@example.com', $this->transport($next)->From);
        $this->assertSame('from@example.com', $next->from);
        $this->assertSame('From', $next->fromName);
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withHeaderAccumulatesHeadersAcrossRebuilds(): void
    {
        $adapter = PhpMailer::fromConfig()
            ->withHeader('X-First', 'one')
            ->withHeader('X-Second', 'two');

        $this->assertSame(
            [['X-First', 'one'], ['X-Second', 'two']],
            $this->transport($adapter)->getCustomHeaders(),
        );
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withHeaderDelegatesToAddCustomHeader(): void
    {
        $adapter = PhpMailer::fromConfig();

        $next = $adapter->withHeader('X-Test', 'value');

        $this->assertNotSame($adapter, $next);
        $this->assertSame([['X-Test', 'value']], $this->transport($next)->getCustomHeaders());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withHtmlDefaultsToTrue(): void
    {
        $adapter = PhpMailer::fromConfig();

        $next = $adapter->withHtml();

        $this->assertNotSame($adapter, $next);
        $this->assertSame('text/html', $this->transport($next)->ContentType);
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withReplyToAccumulatesRecipientsAcrossRebuilds(): void
    {
        $adapter = PhpMailer::fromConfig()
            ->withReplyTo('first@example.com', 'First')
            ->withReplyTo('second@example.com', 'Second');

        $this->assertSame(
            [
                ['first@example.com',  'First'],
                ['second@example.com', 'Second'],
            ],
            $this->transport($adapter)->getReplyToAddresses(),
        );
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withReplyToDelegatesToAddReplyTo(): void
    {
        $adapter = PhpMailer::fromConfig();

        $next = $adapter->withReplyTo('reply@example.com', 'Reply');

        $this->assertNotSame($adapter, $next);
        $this->assertCount(1, $this->transport($next)->getReplyToAddresses());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withSubjectReturnsNewInstanceAndSetsSubject(): void
    {
        $adapter = PhpMailer::fromConfig();

        $next = $adapter->withSubject('Hello');

        $this->assertNotSame($adapter, $next);
        $this->assertSame('Hello', $this->transport($next)->Subject);
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withToAccumulatesRecipientsAcrossRebuilds(): void
    {
        $adapter = PhpMailer::fromConfig()
            ->withTo('first@example.com', 'First')
            ->withTo('second@example.com', 'Second');

        $this->assertSame(
            [
                ['first@example.com',  'First'],
                ['second@example.com', 'Second'],
            ],
            $this->transport($adapter)->getToAddresses(),
        );
    }

    /**
     * @throws MailerException
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withToDelegatesToAddAddress(): void
    {
        $adapter = PhpMailer::fromConfig();

        $next = $adapter->withTo('to@example.com', 'To');

        $this->assertNotSame($adapter, $next);
        $this->assertSame('to@example.com', $this->transport($next)->getToAddresses()[0][0]);
    }

    /**
     * @throws MailerException
     */
    private function mime(PhpMailer $adapter): string
    {
        $mailer = $this->transport($adapter);

        $mailer->preSend();

        return $mailer->getSentMIMEMessage();
    }

    private function transport(PhpMailer $adapter): BaseMailer
    {
        return new ReflectionProperty(PhpMailer::class, 'mailer')->getValue($adapter);
    }
}
