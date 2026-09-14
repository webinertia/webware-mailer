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

namespace Webware\Mailer\Adapter;

use Override;
use SensitiveParameter;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Exception\ExceptionInterface as MimeException;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\AlternativePart;
use Symfony\Component\Mime\Part\TextPart;

/**
 * Symfony Mailer adapter.
 *
 * Transport settings are supplied by the factory and are read-only here; this
 * adapter never sees a message until one is being sent. Symfony separates the
 * transport from the message, so the transport is built once and a fresh `Email`
 * is composed per send — nothing about a message is retained.
 *
 * `transport` selects the Symfony transport and is specific to this
 * implementation: `smtp` (default), `sendmail` or `null`, the last of which
 * accepts messages without sending them. `port` of `0` means "let Symfony
 * decide", which it resolves to 465 when TLS is in force and 25 otherwise.
 *
 * @type SymfonyMailerConfig = array{
 *   transport?: ''|'smtp'|'sendmail'|'null',
 *   host?: non-empty-string,
 *   port?: int<0, 65535>,
 *   smtpSecure?: ''|'tls'|'ssl',
 *   smtpAuth?: bool,
 *   username?: string,
 *   password?: string,
 *   timeout?: positive-int,
 * }
 */
final class SymfonyMailer implements AdapterInterface
{
    private TransportInterface $mailer;

    private function __construct(
        public private(set) string $host,
        #[SensitiveParameter]
        public private(set) string $password,
        public private(set) int $port,
        public private(set) bool $smtpAuth,
        public private(set) string $smtpSecure,
        public private(set) int $timeout,
        public private(set) string $transport,
        public private(set) string $username,
    ) {
        $this->mailer = $this->createTransport();
    }

    /**
     * Builds an adapter from this implementation's own configuration shape.
     *
     * @param SymfonyMailerConfig $config
     */
    public static function fromConfig(array $config = []): self
    {
        return new self(
            host      : $config['host'] ?? 'localhost',
            password  : $config['password'] ?? '',
            port      : $config['port'] ?? 0,
            smtpAuth  : $config['smtpAuth'] ?? false,
            smtpSecure: $config['smtpSecure'] ?? '',
            timeout   : $config['timeout'] ?? 60,
            transport : $config['transport'] ?? 'smtp',
            username  : $config['username'] ?? '',
        );
    }

    /**
     * @throws MimeException
     * @throws TransportExceptionInterface
     */
    #[Override]
    public function send(MessageInterface $message): bool
    {
        return null !== $this->mailer->send($this->buildMessage($message));
    }

    /**
     * @throws MimeException
     */
    private function applyBody(Email $email, MessageInterface $message): void
    {
        $charset  = $this->charset($message);
        $encoding = $this->encoder($message);

        if (! $message->html) {
            $email->setBody(new TextPart($message->body, $charset, 'plain', $encoding));

            return;
        }

        $html = new TextPart($message->body, $charset, 'html', $encoding);

        if ('' === $message->altBody) {
            $email->setBody($html);

            return;
        }

        $email->setBody(
            new AlternativePart(new TextPart($message->altBody, $charset, 'plain', $encoding), $html),
        );
    }

    /**
     * An empty charset means Symfony's own default (`utf-8`); an empty encoding
     * leaves the choice to Symfony, which picks an encoder from the part's
     * content and charset.
     *
     * @throws MimeException
     */
    private function buildMessage(MessageInterface $message): Email
    {
        $email = new Email();

        if ('' !== $message->from) {
            $email->from(new Address($message->from, $message->fromName));
        }

        foreach ($message->to as [$address, $name]) {
            $email->to(new Address($address, $name));
        }

        foreach ($message->cc as [$address, $name]) {
            $email->cc(new Address($address, $name));
        }

        foreach ($message->bcc as [$address, $name]) {
            $email->bcc(new Address($address, $name));
        }

        foreach ($message->replyTo as [$address, $name]) {
            $email->replyTo(new Address($address, $name));
        }

        foreach ($message->headers as [$name, $value]) {
            $email->getHeaders()->addTextHeader($name, $value);
        }

        $email->subject($message->subject);

        $this->applyBody($email, $message);

        foreach ($message->attachments as [$attachment, $name, $mimeType, $isRawContent]) {
            $email->addPart(
                $isRawContent
                    ? new DataPart($attachment, $name, $mimeType, $this->encoder($message))
                    : DataPart::fromPath($attachment, $name, $mimeType),
            );
        }

        return $email;
    }

    private function charset(MessageInterface $message): string
    {
        return '' === $message->charset ? 'utf-8' : $message->charset;
    }

    private function createSmtpTransport(): EsmtpTransport
    {
        // The transport writes host, port and TLS onto the stream it is given,
        // so the timeout set here survives alongside them.
        $stream = new SocketStream();
        $stream->setTimeout((float) $this->timeout);

        $transport = new EsmtpTransport(
            host  : $this->host,
            port  : $this->port,
            tls   : $this->resolveTls(),
            stream: $stream,
        );

        if ($this->smtpAuth) {
            $transport->setUsername($this->username);
            $transport->setPassword($this->password);
        }

        return $transport;
    }

    private function createTransport(): TransportInterface
    {
        return match ($this->transport) {
            'sendmail' => new SendmailTransport(),
            'null'     => new NullTransport(),
            default    => $this->createSmtpTransport(),
        };
    }

    private function encoder(MessageInterface $message): ?string
    {
        return '' === $message->encoding ? null : $message->encoding;
    }

    /**
     * An empty `smtpSecure` leaves the decision to Symfony, which infers it from
     * the port and the host.
     */
    private function resolveTls(): ?bool
    {
        return match ($this->smtpSecure) {
            'tls', 'ssl' => true,
            default      => null,
        };
    }
}
