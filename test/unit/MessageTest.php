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
use Webware\Mailer\Message;

use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

#[CoversClass(Message::class)]
#[CoversMethod(Message::class, '__construct')]
#[CoversMethod(Message::class, 'withAltBody')]
#[CoversMethod(Message::class, 'withAttachment')]
#[CoversMethod(Message::class, 'withAttachmentFromString')]
#[CoversMethod(Message::class, 'withBcc')]
#[CoversMethod(Message::class, 'withBody')]
#[CoversMethod(Message::class, 'withCc')]
#[CoversMethod(Message::class, 'withCharset')]
#[CoversMethod(Message::class, 'withEncoding')]
#[CoversMethod(Message::class, 'withFrom')]
#[CoversMethod(Message::class, 'withHeader')]
#[CoversMethod(Message::class, 'withHtml')]
#[CoversMethod(Message::class, 'withReplyTo')]
#[CoversMethod(Message::class, 'withSubject')]
#[CoversMethod(Message::class, 'withTo')]
final class MessageTest extends TestCase
{
    /**
     * Every setting carried forward together: one chain, all state on the last
     * instance, and the base untouched.
     *
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function aChainCarriesEverySettingForward(): void
    {
        $base = new Message();

        $message = $base->withTo('to@example.com', 'To')
            ->withFrom('from@example.com', 'From')
            ->withSubject('Subject')
            ->withBody('Body')
            ->withAltBody('Alt')
            ->withHtml()
            ->withCharset('utf-8')
            ->withEncoding('8bit');

        $this->assertSame([['to@example.com', 'To']], $message->to);
        $this->assertSame('from@example.com', $message->from);
        $this->assertSame('From', $message->fromName);
        $this->assertSame('Subject', $message->subject);
        $this->assertSame('Body', $message->body);
        $this->assertSame('Alt', $message->altBody);
        $this->assertTrue($message->html);
        $this->assertSame('utf-8', $message->charset);
        $this->assertSame('8bit', $message->encoding);

        $this->assertSame([], $base->to);
        $this->assertSame('', $base->subject);
        $this->assertSame('', $base->body);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function aNewMessageCarriesNothing(): void
    {
        $message = new Message();

        $this->assertSame('', $message->altBody);
        $this->assertSame([], $message->attachments);
        $this->assertSame([], $message->bcc);
        $this->assertSame('', $message->body);
        $this->assertSame([], $message->cc);
        $this->assertSame('', $message->charset);
        $this->assertSame('', $message->encoding);
        $this->assertSame('', $message->from);
        $this->assertSame('', $message->fromName);
        $this->assertSame([], $message->headers);
        $this->assertFalse($message->html);
        $this->assertSame([], $message->replyTo);
        $this->assertSame('', $message->subject);
        $this->assertSame([], $message->to);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function attachmentsAccumulateAcrossRebuilds(): void
    {
        $base = new Message();

        $message = $base->withAttachmentFromString('first', 'first.txt', 'text/plain')
            ->withAttachmentFromString('second', 'second.txt', 'text/plain')
            ->withAttachment('/tmp/third.txt', 'third.txt', 'text/plain');

        $this->assertSame(
            [
                ['first',          'first.txt',  'text/plain', true],
                ['second',         'second.txt', 'text/plain', true],
                ['/tmp/third.txt', 'third.txt',  'text/plain', false],
            ],
            $message->attachments,
        );
        $this->assertSame([], $base->attachments);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function headersAccumulateAcrossRebuilds(): void
    {
        $base = new Message();

        $message = $base->withHeader('X-First', 'one')
            ->withHeader('X-Second', 'two');

        $this->assertSame([['X-First', 'one'], ['X-Second', 'two']], $message->headers);
        $this->assertSame([], $base->headers);
    }

    /**
     * Recipients accumulate along a chain, and each step leaves the instance it
     * was called on alone.
     *
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function recipientListsAccumulateAcrossRebuilds(): void
    {
        $base = new Message();

        $message = $base->withTo('first@example.com', 'First')
            ->withTo('second@example.com', 'Second')
            ->withCc('cc@example.com', 'Cc')
            ->withCc('cc2@example.com', 'Cc Two')
            ->withBcc('bcc@example.com', 'Bcc')
            ->withBcc('bcc2@example.com', 'Bcc Two')
            ->withReplyTo('reply@example.com', 'Reply')
            ->withReplyTo('reply2@example.com', 'Reply Two');

        $this->assertSame(
            [
                ['first@example.com',  'First'],
                ['second@example.com', 'Second'],
            ],
            $message->to,
        );
        $this->assertSame(
            [
                ['cc@example.com',  'Cc'],
                ['cc2@example.com', 'Cc Two'],
            ],
            $message->cc,
        );
        $this->assertSame(
            [
                ['bcc@example.com',  'Bcc'],
                ['bcc2@example.com', 'Bcc Two'],
            ],
            $message->bcc,
        );
        $this->assertSame(
            [
                ['reply@example.com',  'Reply'],
                ['reply2@example.com', 'Reply Two'],
            ],
            $message->replyTo,
        );

        $this->assertSame([], $base->to);
        $this->assertSame([], $base->cc);
        $this->assertSame([], $base->bcc);
        $this->assertSame([], $base->replyTo);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withAltBodyReturnsANewInstanceAndSetsTheAlternativeBody(): void
    {
        $message = new Message();
        $next    = $message->withAltBody('plain');

        $this->assertNotSame($message, $next);
        $this->assertSame('', $message->altBody);
        $this->assertSame('plain', $next->altBody);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withAttachmentFromStringRecordsRawContent(): void
    {
        $message = new Message();
        $next    = $message->withAttachmentFromString('content', 'file.txt', 'text/plain');

        $this->assertSame([], $message->attachments);
        $this->assertSame([['content', 'file.txt', 'text/plain', true]], $next->attachments);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withAttachmentRecordsAFileAttachment(): void
    {
        $path = (string) tempnam(
            directory: sys_get_temp_dir(),
            prefix   : 'mailer-message',
        );

        file_put_contents(
            filename: $path,
            data    : 'content',
        );

        $message = new Message();
        $next    = $message->withAttachment($path, 'file.txt', 'text/plain');

        $this->assertNotSame($message, $next);
        $this->assertSame([], $message->attachments);
        $this->assertSame([[$path, 'file.txt', 'text/plain', false]], $next->attachments);

        unlink(filename: $path);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withBodyReturnsANewInstanceAndSetsTheBody(): void
    {
        $message = new Message();
        $next    = $message->withBody('<p>html</p>');

        $this->assertNotSame($message, $next);
        $this->assertSame('', $message->body);
        $this->assertSame('<p>html</p>', $next->body);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withCharsetReturnsANewInstanceAndSetsTheCharset(): void
    {
        $message = new Message();
        $next    = $message->withCharset('iso-8859-1');

        $this->assertNotSame($message, $next);
        $this->assertSame('', $message->charset);
        $this->assertSame('iso-8859-1', $next->charset);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withEncodingReturnsANewInstanceAndSetsTheEncoding(): void
    {
        $message = new Message();
        $next    = $message->withEncoding('quoted-printable');

        $this->assertNotSame($message, $next);
        $this->assertSame('', $message->encoding);
        $this->assertSame('quoted-printable', $next->encoding);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withFromSetsTheSenderAndDisplayName(): void
    {
        $message = new Message();
        $next    = $message->withFrom('from@example.com', 'Example Sender');

        $this->assertNotSame($message, $next);
        $this->assertSame('', $message->from);
        $this->assertSame('', $message->fromName);
        $this->assertSame('from@example.com', $next->from);
        $this->assertSame('Example Sender', $next->fromName);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withHtmlDefaultsToTrue(): void
    {
        $message = new Message();
        $next    = $message->withHtml();

        $this->assertFalse($message->html);
        $this->assertTrue($next->html);
        $this->assertFalse($next->withHtml(false)->html);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function withSubjectReturnsANewInstanceAndSetsTheSubject(): void
    {
        $message = new Message();
        $next    = $message->withSubject('Subject');

        $this->assertNotSame($message, $next);
        $this->assertSame('', $message->subject);
        $this->assertSame('Subject', $next->subject);
    }
}
