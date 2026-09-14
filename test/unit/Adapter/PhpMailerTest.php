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

use LogicException;
use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer as BaseMailer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use Webware\Mailer\Adapter\PhpMailer;
use Webware\Mailer\Message;

use function array_column;
use function basename;
use function bin2hex;
use function file_put_contents;
use function random_bytes;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * The adapter owns transport settings only; the transport it builds is held for
 * the adapter's lifetime and each send clears the previous message off it before
 * applying its own. These tests drive that path without touching the network.
 */
#[CoversClass(PhpMailer::class)]
#[CoversMethod(PhpMailer::class, '__construct')]
#[CoversMethod(PhpMailer::class, 'applyMessage')]
#[CoversMethod(PhpMailer::class, 'createTransport')]
#[CoversMethod(PhpMailer::class, 'fromConfig')]
#[CoversMethod(PhpMailer::class, 'isMail')]
#[CoversMethod(PhpMailer::class, 'isSmtp')]
#[CoversMethod(PhpMailer::class, 'resetMessageState')]
#[CoversMethod(PhpMailer::class, 'send')]
final class PhpMailerTest extends TestCase
{
    /**
     * An empty charset or encoding leaves PHPMailer's own default in force.
     *
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function anEmptyCharsetAndEncodingLeaveTheLibraryDefaultsAlone(): void
    {
        $defaults  = new BaseMailer();
        $transport = $this->transport(PhpMailer::fromConfig());

        $this->assertSame($defaults->CharSet, $transport->CharSet);
        $this->assertSame($defaults->Encoding, $transport->Encoding);
    }

    /**
     * An empty sender leaves the transport without one.
     *
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function anEmptyFromIsNotApplied(): void
    {
        $this->assertSame('', $this->transport(PhpMailer::fromConfig())->From);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function attachmentsAreAppliedAsBase64(): void
    {
        $path = (string) tempnam(
            directory: sys_get_temp_dir(),
            prefix   : 'mailer-adapter',
        );

        file_put_contents(
            filename: $path,
            data    : 'content',
        );

        $raw = $this->transport(
            PhpMailer::fromConfig(),
            new Message(attachments: [['raw content', 'raw.txt', 'text/plain', true]]),
        );

        $this->assertCount(1, $raw->getAttachments());

        // A raw attachment must not stop the ones that follow it from being applied.
        $both = $this->transport(
            PhpMailer::fromConfig(),
            new Message(attachments: [
                ['raw content', 'raw.txt',  'text/plain', true],
                [$path,         'file.txt', 'text/plain', false],
            ]),
        );

        $this->assertSame(
            ['raw.txt', basename($path)],
            array_column($both->getAttachments(), 1),
        );

        unlink(filename: $path);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function defaultsMirrorTheTransport(): void
    {
        $adapter = PhpMailer::fromConfig();

        $this->assertTrue($adapter->enableExceptions);
        $this->assertSame('localhost', $adapter->host);
        $this->assertSame('', $adapter->password);
        $this->assertSame(25, $adapter->port);
        $this->assertFalse($adapter->smtpAuth);
        $this->assertSame('', $adapter->smtpSecure);
        $this->assertSame(300, $adapter->timeout);
        $this->assertSame('', $adapter->username);
        $this->assertFalse($adapter->useSmtp);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function fromConfigExposesTheTransportContract(): void
    {
        $adapter = PhpMailer::fromConfig([
            'enableExceptions' => false,
            'host'             => 'smtp.example.com',
            'password'         => bin2hex(random_bytes(16)),
            'port'             => 587,
            'smtpAuth'         => true,
            'smtpSecure'       => 'tls',
            'timeout'          => 45,
            'username'         => 'user',
            'useSmtp'          => true,
        ]);

        $this->assertFalse($adapter->enableExceptions);
        $this->assertSame('smtp.example.com', $adapter->host);
        $this->assertNotSame('', $adapter->password);
        $this->assertSame(587, $adapter->port);
        $this->assertTrue($adapter->smtpAuth);
        $this->assertSame('tls', $adapter->smtpSecure);
        $this->assertSame(45, $adapter->timeout);
        $this->assertSame('user', $adapter->username);
        $this->assertTrue($adapter->useSmtp);
        $this->assertFalse($adapter->smtpKeepAlive);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function sendDelegatesToTheTransport(): void
    {
        $this->expectException(MailerException::class);

        PhpMailer::fromConfig(['enableExceptions' => true])->send(new Message());
    }

    /**
     * The adapter keeps one transport for its whole life, so each send has to
     * clear what the previous message left on it.
     *
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function sendsDoNotAccumulateRecipients(): void
    {
        $adapter = PhpMailer::fromConfig();

        $this->attemptSend($adapter, new Message(
            attachments: [['raw content', 'raw.txt', 'text/plain', true]],
            headers    : [['X-Test', 'value']],
            subject    : 'First',
            to         : [['alice@example.com', '']],
        ));

        $this->assertSame([['alice@example.com', '']], $this->transport($adapter)->getToAddresses());

        $this->attemptSend($adapter, new Message(to: [['bob@example.com', '']]));

        $transport = $this->transport($adapter);

        $this->assertSame([['bob@example.com', '']], $transport->getToAddresses());
        $this->assertSame('', $transport->Subject);
        $this->assertSame([], $transport->getCustomHeaders());
        $this->assertSame([], $transport->getAttachments());
        $this->assertSame('', $transport->Body);
        $this->assertSame('', $transport->AltBody);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function theMessageIsAppliedOnTheTransport(): void
    {
        $transport = $this->transport(PhpMailer::fromConfig(), new Message(
            altBody : 'plain',
            body    : '<p>html</p>',
            charset : 'utf-8',
            encoding: 'quoted-printable',
            from    : 'from@example.com',
            fromName: 'Example Sender',
            headers : [['X-Test', 'value']],
            html    : true,
            replyTo : [['reply@example.com', 'Reply']],
            subject : 'Subject',
            to      : [['to@example.com', 'To']],
            cc      : [['cc@example.com', 'Cc']],
            bcc     : [['bcc@example.com', 'Bcc']],
        ));

        $this->assertSame('utf-8', $transport->CharSet);
        $this->assertSame('quoted-printable', $transport->Encoding);
        $this->assertSame('from@example.com', $transport->From);
        $this->assertSame('Example Sender', $transport->FromName);
        $this->assertSame('Subject', $transport->Subject);
        $this->assertSame('<p>html</p>', $transport->Body);
        $this->assertSame('plain', $transport->AltBody);
        $this->assertSame('text/html', $transport->ContentType);
        $this->assertSame([['to@example.com', 'To']], $transport->getToAddresses());
        $this->assertSame([['cc@example.com', 'Cc']], $transport->getCcAddresses());
        $this->assertSame([['bcc@example.com', 'Bcc']], $transport->getBccAddresses());
        $this->assertCount(1, $transport->getReplyToAddresses());
        $this->assertSame([['X-Test', 'value']], $transport->getCustomHeaders());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function thePredicatesReportTheConfiguredTransport(): void
    {
        $this->assertTrue(PhpMailer::fromConfig()->isMail());
        $this->assertFalse(PhpMailer::fromConfig()->isMail() && false);

        $smtp = PhpMailer::fromConfig(['useSmtp' => true]);

        $this->assertTrue($smtp->isSmtp());
        $this->assertFalse($smtp->isMail());

        $mail = PhpMailer::fromConfig();

        $this->assertFalse($mail->isSmtp());
        $this->assertTrue($mail->isMail());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function theTransportIsConfiguredFromTheAdapter(): void
    {
        $transport = $this->transport(PhpMailer::fromConfig([
            'enableExceptions' => true,
            'host'             => 'smtp.example.com',
            'port'             => 587,
            'smtpAuth'         => true,
            'smtpKeepAlive'    => true,
            'smtpSecure'       => 'tls',
            'timeout'          => 45,
            'username'         => 'user',
            'useSmtp'          => true,
        ]));

        $this->assertSame('smtp', $transport->Mailer);
        $this->assertSame('smtp.example.com', $transport->Host);
        $this->assertSame(587, $transport->Port);
        $this->assertTrue($transport->SMTPAuth);
        $this->assertTrue($transport->SMTPKeepAlive);
        $this->assertSame('user', $transport->Username);
        $this->assertSame('tls', $transport->SMTPSecure);
        $this->assertSame(45, $transport->Timeout);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    private function attemptSend(PhpMailer $adapter, Message $message): void
    {
        try {
            $adapter->send($message);
        } catch (MailerException $exception) {
            // There is no mail transport in the test environment, so the send is
            // expected to fail; the state it left on the shared transport is what
            // these tests inspect.
            $this->assertNotSame('', $exception->getMessage());
        }
    }

    /**
     * The transport the adapter holds, with an optional message applied to it.
     * `applyMessage()` is what `send()` runs against PHPMailer, so driving it
     * directly keeps message assertions off the network.
     *
     * @throws \PHPUnit\Exception
     */
    private function transport(PhpMailer $adapter, ?Message $message = null): BaseMailer
    {
        if ($message instanceof Message) {
            new ReflectionMethod(PhpMailer::class, 'applyMessage')->invoke($adapter, $message);
        }

        /** @var mixed $transport */
        $transport = new ReflectionProperty(PhpMailer::class, 'mailer')->getValue($adapter);

        if (! $transport instanceof BaseMailer) {
            throw new LogicException('Expected a BaseMailer instance.');
        }

        return $transport;
    }
}
