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

namespace WebwareTest\Mailer\Http\Middleware;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\Http\Middleware\MailerMiddleware;
use Webware\Mailer\Mailer;
use Webware\Mailer\MailerInterface;

#[CoversClass(MailerMiddleware::class)]
#[CoversMethod(MailerMiddleware::class, '__construct')]
#[CoversMethod(MailerMiddleware::class, 'process')]
final class MailerMiddlewareTest extends TestCase
{
    /**
     * The middleware carries no configuration of its own: it injects the mailer
     * it was built with and nothing else.
     *
     * @throws \PHPUnit\Exception
     */
    #[Test]
    public function processInjectsTheMailerIntoTheRequest(): void
    {
        $mailer               = new Mailer($this->createStub(AdapterInterface::class));
        $requestWithAttribute = $this->createStub(ServerRequestInterface::class);
        $capturedAttribute    = null;
        $capturedValue        = null;

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('withAttribute')
            ->willReturnCallback(
                static function (string $attribute, mixed $value) use (
                    $requestWithAttribute,
                    &$capturedAttribute,
                    &$capturedValue,
                ): ServerRequestInterface {
                    $capturedAttribute = $attribute;
                    $capturedValue     = $value;

                    return $requestWithAttribute;
                },
            );

        $response = $this->createStub(ResponseInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($requestWithAttribute)->willReturn($response);

        $middleware = new MailerMiddleware($mailer);

        $this->assertSame($response, $middleware->process($request, $handler));
        $this->assertSame(MailerInterface::class, $capturedAttribute);
        $this->assertSame($mailer, $capturedValue);
    }
}
