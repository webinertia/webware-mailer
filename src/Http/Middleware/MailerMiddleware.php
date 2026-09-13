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

namespace Webware\Mailer\Http\Middleware;

use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Mailer\MailerInterface;

/**
 * Makes the configured mailer available to the request handler. The adapter's
 * settings, including its default sender, come from the adapter contract — the
 * middleware carries no configuration of its own.
 */
final readonly class MailerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private MailerInterface $mailer,
    ) {}

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request->withAttribute(MailerInterface::class, $this->mailer));
    }
}
