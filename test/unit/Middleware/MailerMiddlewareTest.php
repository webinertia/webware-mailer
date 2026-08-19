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

namespace WebwareTest\Mailer\Middleware;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\Mailer;
use Webware\Mailer\MailerInterface;
use Webware\Mailer\Middleware\MailerMiddleware;

#[CoversClass(MailerMiddleware::class)]
#[CoversMethod(MailerMiddleware::class, 'process')]
final class MailerMiddlewareTest extends TestCase
{
    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function processContinuesWithoutAdapter(): void
    {
        $mailer = new Mailer(null);

        $requestWithAttribute = $this->createStub(ServerRequestInterface::class);
        $request              = $this->createStub(ServerRequestInterface::class);
        $request->method('withAttribute')->willReturn($requestWithAttribute);

        $response = $this->createStub(ResponseInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($requestWithAttribute)->willReturn($response);

        $middleware = new MailerMiddleware($mailer, []);

        $this->assertSame($response, $middleware->process($request, $handler));
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function processSetsFromAddressAndInjectsMailer(): void
    {
        $mailer = new Mailer($this->createStub(AdapterInterface::class));

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())->method('from')->with('sender@example.com');

        $mailer->setAdapter($adapter);

        $requestWithAttribute = $this->createStub(ServerRequestInterface::class);
        $request              = $this->createStub(ServerRequestInterface::class);
        $capturedAttribute    = '';
        $capturedValue        = null;
        $request->method('withAttribute')
            ->willReturnCallback(
                static function (string $attribute, mixed $value) use (
                    $requestWithAttribute,
                    &$capturedAttribute,
                    &$capturedValue,
                ): ServerRequestInterface {
                    $capturedAttribute = $attribute;

                    if ($value instanceof MailerInterface) {
                        $capturedValue = $value;
                    }

                    return $requestWithAttribute;
                },
            );

        $response = $this->createStub(ResponseInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($requestWithAttribute)->willReturn($response);

        $middleware = new MailerMiddleware($mailer, [MailerMiddleware::FROM_ADDRESS_KEY => 'sender@example.com']);

        $this->assertSame($response, $middleware->process($request, $handler));
        $this->assertSame(MailerInterface::class, $capturedAttribute);
        $this->assertSame($mailer, $capturedValue);
    }

    /**
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function processSkipsFromWhenConfigMissing(): void
    {
        $mailer  = new Mailer(null);
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->never())->method('from');

        $mailer->setAdapter($adapter);

        $requestWithAttribute = $this->createStub(ServerRequestInterface::class);
        $request              = $this->createStub(ServerRequestInterface::class);
        $request->method('withAttribute')->willReturn($requestWithAttribute);

        $response = $this->createStub(ResponseInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($requestWithAttribute)->willReturn($response);

        $middleware = new MailerMiddleware($mailer, []);

        $this->assertSame($response, $middleware->process($request, $handler));
    }
}
