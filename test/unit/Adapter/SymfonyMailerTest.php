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
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\ParameterizedHeader;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\AlternativePart;
use Symfony\Component\Mime\Part\TextPart;
use Webware\Mailer\Adapter\SymfonyMailer;
use Webware\Mailer\Message;

use function bin2hex;
use function file_put_contents;
use function is_string;
use function random_bytes;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Symfony separates the transport from the message, so the adapter builds its
 * transport once and composes a fresh `Email` per send. These tests inspect both
 * halves directly rather than talking to an SMTP server.
 */
#[CoversClass(SymfonyMailer::class)]
#[CoversMethod(SymfonyMailer::class, '__construct')]
#[CoversMethod(SymfonyMailer::class, 'applyBody')]
#[CoversMethod(SymfonyMailer::class, 'buildMessage')]
#[CoversMethod(SymfonyMailer::class, 'charset')]
#[CoversMethod(SymfonyMailer::class, 'createSmtpTransport')]
#[CoversMethod(SymfonyMailer::class, 'createTransport')]
#[CoversMethod(SymfonyMailer::class, 'encoder')]
#[CoversMethod(SymfonyMailer::class, 'fromConfig')]
#[CoversMethod(SymfonyMailer::class, 'resolveTls')]
#[CoversMethod(SymfonyMailer::class, 'send')]
final class SymfonyMailerTest extends TestCase
{
    private string $password = '';

    /**
     * An empty sender leaves the email without one.
     *
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function anEmptyFromIsNotApplied(): void
    {
        $this->assertSame([], $this->built(new Message(body: 'body'))->getFrom());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function anHtmlMessageBecomesAnHtmlPart(): void
    {
        $email = $this->built(new Message(
            body: '<p>html</p>',
            html: true,
        ));

        $body = $email->getBody();

        $this->assertInstanceOf(TextPart::class, $body);
        $this->assertSame('html', $body->getMediaSubtype());
        $this->assertSame('<p>html</p>', $body->getBody());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function anHtmlMessageWithAnAlternativeBecomesAnAlternativePart(): void
    {
        $email = $this->built(new Message(
            altBody: 'plain alternative',
            body   : '<p>html</p>',
            html   : true,
        ));

        $body = $email->getBody();

        $this->assertInstanceOf(AlternativePart::class, $body);

        [$plain, $html] = $body->getParts();

        $this->assertInstanceOf(TextPart::class, $plain);
        $this->assertInstanceOf(TextPart::class, $html);
        $this->assertSame('plain alternative', $plain->getBody());
        $this->assertSame('<p>html</p>', $html->getBody());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function anSslTransportIsMarkedAsTls(): void
    {
        $adapter = SymfonyMailer::fromConfig([
            'host'       => 'smtp.example.com',
            'port'       => 465,
            'smtpSecure' => 'ssl',
        ]);

        $transport = $this->transport($adapter);

        $this->assertInstanceOf(EsmtpTransport::class, $transport);
        $this->assertTrue($transport->getStream()->isTLS());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function aPlainMessageBecomesAPlainTextPart(): void
    {
        $email = $this->built(new Message(body: 'plain body'));

        $body = $email->getBody();

        $this->assertInstanceOf(TextPart::class, $body);
        $this->assertSame('plain', $body->getMediaSubtype());
        $this->assertSame('plain body', $body->getBody());
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function attachmentsAreAddedAsParts(): void
    {
        $path = (string) tempnam(
            directory: sys_get_temp_dir(),
            prefix   : 'mailer-symfony',
        );

        file_put_contents(
            filename: $path,
            data    : 'content',
        );

        $email = $this->built(new Message(
            attachments: [
                ['raw content', 'raw.txt',  'text/plain', true],
                [$path,         'file.txt', 'text/plain', false],
            ],
            body       : 'body',
        ));

        $attachments = $email->getAttachments();

        $this->assertCount(2, $attachments);
        $this->assertInstanceOf(DataPart::class, $attachments[0]);
        $this->assertSame('raw.txt', $attachments[0]->getFilename());
        $this->assertSame('raw content', $attachments[0]->getBody());
        $this->assertSame('file.txt', $attachments[1]->getFilename());
        $this->assertSame('content', $attachments[1]->getBody());

        unlink(filename: $path);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function defaultsMirrorTheTransport(): void
    {
        $adapter = SymfonyMailer::fromConfig();

        $this->assertSame('localhost', $adapter->host);
        $this->assertSame('', $adapter->password);
        $this->assertSame(0, $adapter->port);
        $this->assertFalse($adapter->smtpAuth);
        $this->assertSame('', $adapter->smtpSecure);
        $this->assertSame(60, $adapter->timeout);
        $this->assertSame('smtp', $adapter->transport);
        $this->assertSame('', $adapter->username);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function fromConfigExposesTheTransportContract(): void
    {
        $adapter = SymfonyMailer::fromConfig([
            'host'       => 'smtp.example.com',
            'password'   => $this->password,
            'port'       => 587,
            'smtpAuth'   => true,
            'smtpSecure' => 'tls',
            'timeout'    => 45,
            'transport'  => 'smtp',
            'username'   => 'user',
        ]);

        $this->assertSame('smtp.example.com', $adapter->host);
        $this->assertSame($this->password, $adapter->password);
        $this->assertSame(587, $adapter->port);
        $this->assertTrue($adapter->smtpAuth);
        $this->assertSame('tls', $adapter->smtpSecure);
        $this->assertSame(45, $adapter->timeout);
        $this->assertSame('smtp', $adapter->transport);
        $this->assertSame('user', $adapter->username);
    }

    /**
     * The null transport accepts the message without performing any I/O, so this
     * covers `send()` and the whole message composition path together.
     *
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function sendingThroughTheNullTransportReportsSuccess(): void
    {
        $adapter = SymfonyMailer::fromConfig(['transport' => 'null']);

        $this->assertTrue($adapter->send(new Message(
            body   : 'body',
            from   : 'from@example.com',
            subject: 'Subject',
            to     : [['to@example.com', '']],
        )));
    }

    /**
     * An empty charset means Symfony's own default; a set one is carried through
     * into the part's Content-Type.
     *
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function theCharsetFallsBackToUtf8(): void
    {
        $default = $this->built(new Message(body: 'body'))->getBody();

        $this->assertInstanceOf(TextPart::class, $default);
        $this->assertSame('utf-8', $this->contentTypeParameter($default));

        $explicit = $this->built(new Message(
            body   : 'body',
            charset: 'iso-8859-1',
        ))->getBody();

        $this->assertInstanceOf(TextPart::class, $explicit);
        $this->assertSame('iso-8859-1', $this->contentTypeParameter($explicit));
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function theConfiguredTransportIsSelected(): void
    {
        $this->assertInstanceOf(
            NullTransport::class,
            $this->transport(SymfonyMailer::fromConfig([
                'transport' => 'null',
            ])),
        );
        $this->assertInstanceOf(
            SendmailTransport::class,
            $this->transport(SymfonyMailer::fromConfig([
                'transport' => 'sendmail',
            ])),
        );
        $this->assertInstanceOf(
            EsmtpTransport::class,
            $this->transport(SymfonyMailer::fromConfig([
                'transport' => 'smtp',
            ])),
        );
        $this->assertInstanceOf(EsmtpTransport::class, $this->transport(SymfonyMailer::fromConfig()));
    }

    /**
     * A set encoding is carried into the part's Content-Transfer-Encoding; an
     * empty one leaves the choice to Symfony.
     *
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function theEncodingIsAppliedWhenSet(): void
    {
        $encoded = $this->built(new Message(
            body    : 'body',
            encoding: 'base64',
        ))->getBody();

        $this->assertInstanceOf(TextPart::class, $encoded);
        $this->assertSame('base64', $this->transferEncoding($encoded));
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function theMessageAddressesHeadersAndSubjectAreApplied(): void
    {
        $email = $this->built(new Message(
            bcc     : [['bcc@example.com', 'Bcc']],
            cc      : [['cc@example.com', 'Cc']],
            from    : 'from@example.com',
            fromName: 'Example Sender',
            headers : [['X-Test', 'value']],
            replyTo : [['reply@example.com', 'Reply']],
            subject : 'Subject',
            to      : [['to@example.com', 'To']],
        ));

        $this->assertSame('from@example.com', $email->getFrom()[0]->getAddress());
        $this->assertSame('Example Sender', $email->getFrom()[0]->getName());
        $this->assertSame('to@example.com', $email->getTo()[0]->getAddress());
        $this->assertSame('cc@example.com', $email->getCc()[0]->getAddress());
        $this->assertSame('bcc@example.com', $email->getBcc()[0]->getAddress());
        $this->assertSame('reply@example.com', $email->getReplyTo()[0]->getAddress());
        $this->assertSame('Subject', $email->getSubject());
        $this->assertSame('value', $email->getHeaders()->getHeaderBody('X-Test'));
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function theSmtpTransportCarriesTheConfiguredSettings(): void
    {
        $adapter = SymfonyMailer::fromConfig([
            'host'       => 'smtp.example.com',
            'password'   => $this->password,
            'port'       => 587,
            'smtpAuth'   => true,
            'smtpSecure' => 'tls',
            'timeout'    => 45,
            'username'   => 'user',
        ]);

        $transport = $this->transport($adapter);

        $this->assertInstanceOf(EsmtpTransport::class, $transport);

        $stream = $transport->getStream();

        $this->assertInstanceOf(SocketStream::class, $stream);
        $this->assertSame('smtp.example.com', $stream->getHost());
        $this->assertSame(587, $stream->getPort());
        $this->assertTrue($stream->isTLS());
        $this->assertSame(45.0, $stream->getTimeout());
        $this->assertSame('user', $transport->getUsername());
        $this->assertSame($this->password, $transport->getPassword());
    }

    /**
     * Without `smtpAuth` no credentials are pushed onto the transport, and an
     * empty `smtpSecure` leaves the TLS decision to Symfony.
     *
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function theSmtpTransportOmitsCredentialsUnlessAskedForThem(): void
    {
        $adapter = SymfonyMailer::fromConfig([
            'host'     => 'smtp.example.com',
            'password' => $this->password,
            'port'     => 25,
            'username' => 'user',
        ]);

        $transport = $this->transport($adapter);

        $this->assertInstanceOf(EsmtpTransport::class, $transport);
        $this->assertSame('', $transport->getUsername());
        $this->assertSame('', $transport->getPassword());
        $this->assertFalse($transport->getStream()->isTLS());
    }

    /**
     * Credentials are generated so no password literal sits in the suite.
     */
    #[Override]
    protected function setUp(): void
    {
        $this->password = bin2hex(random_bytes(16));
    }

    /**
     * The message this adapter would hand to Symfony, without sending it.
     *
     * @throws \PHPUnit\Exception
     */
    private function built(Message $message): Email
    {
        $adapter = SymfonyMailer::fromConfig(['transport' => 'null']);

        /** @var mixed $email */
        $email = new ReflectionMethod(SymfonyMailer::class, 'buildMessage')->invoke($adapter, $message);

        if (! $email instanceof Email) {
            throw new LogicException('Expected an Email instance.');
        }

        return $email;
    }

    /**
     * @throws \PHPUnit\Exception
     */
    private function contentTypeParameter(TextPart|DataPart $part): ?string
    {
        $header = $part->getPreparedHeaders()->get('Content-Type');

        if (! $header instanceof ParameterizedHeader) {
            throw new LogicException('Expected a parameterised Content-Type header.');
        }

        return $header->getParameter('charset');
    }

    /**
     * @param non-empty-string $name
     */
    private function header(TextPart|DataPart $part, string $name): string
    {
        $value = $part->getPreparedHeaders()->getHeaderBody($name);

        if (! is_string($value)) {
            throw new LogicException('Expected a string header value.');
        }

        return $value;
    }

    /**
     * @throws \PHPUnit\Exception
     */
    private function transferEncoding(TextPart|DataPart $part): string
    {
        return $this->header($part, 'Content-Transfer-Encoding');
    }

    /**
     * @throws \PHPUnit\Exception
     */
    private function transport(SymfonyMailer $adapter): TransportInterface
    {
        /** @var mixed $transport */
        $transport = new ReflectionProperty(SymfonyMailer::class, 'mailer')->getValue($adapter);

        if (! $transport instanceof TransportInterface) {
            throw new LogicException('Expected a TransportInterface instance.');
        }

        return $transport;
    }
}
