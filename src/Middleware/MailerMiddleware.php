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

use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Mailer\MailerInterface;

use function is_string;

final class MailerMiddleware implements MiddlewareInterface
{
    final public const string TEMPLATE_KEY = 'message_templates';

    final public const string FROM_ADDRESS_KEY = 'from';

    /**
     * @param array<array-key, mixed> $config
     */
    public function __construct(
        private MailerInterface $mailer,
        private array $config,
    ) {}

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $adapter = $this->mailer->getAdapter();
        if (null !== $adapter) {
            if (is_string($this->config[static::FROM_ADDRESS_KEY] ?? null)) {
                $adapter->from($this->config[static::FROM_ADDRESS_KEY]);
            }
        }

        return $handler->handle($request->withAttribute(MailerInterface::class, $this->mailer));
    }
}
