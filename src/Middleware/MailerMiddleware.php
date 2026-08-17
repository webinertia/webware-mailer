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

namespace Webware\Mailer\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Mailer\MailerInterface;

class MailerMiddleware implements MiddlewareInterface
{
    final public const TEMPLATE_KEY = 'message_templates';

    final public const FROM_ADDRESS_KEY = 'from';

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private MailerInterface $mailer,
        private array $config,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $adapter = $this->mailer->getAdapter();
        if ($adapter !== null) {
            $adapter->from((string) $this->config[static::FROM_ADDRESS_KEY]);
        }

        return $handler->handle($request->withAttribute(MailerInterface::class, $this->mailer));
    }
}
