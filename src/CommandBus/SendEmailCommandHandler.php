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

namespace Webware\Mailer\CommandBus;

use Exception;
use RuntimeException;
use Webware\Mailer\MailerInterface;
use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;

final readonly class SendEmailCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private MailerInterface $mailer,
    ) {}

    /**
     * @param CommandInterface&SendEmailCommand $command
     */
    public function handle(CommandInterface $command): CommandResultInterface
    {
        try {
            $adapter = $this->mailer->getAdapter();
            if (null === $adapter) {
                throw new RuntimeException('No adapter configured on Mailer instance.');
            }
            $adapter->to($command->getTo())
                ->from($command->getFrom())
                ->subject($command->getSubject())
                ->body($command->getBody());
            $this->mailer->send();
        } catch (Exception $e) { // track down the specific exception thrown by the mailer adapter and catch that instead of Exception
            return new CommandResult(
                $command,
                MessageStatus::Failure,
                $e->getMessage(),
            );
        }

        return new CommandResult(
            $command,
            MessageStatus::Success,
            'Email sent successfully',
        );
    }
}
