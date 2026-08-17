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
use Override;
use RuntimeException;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandResultInterface;
use Webware\CommandBus\Command\CommandStatus;
use Webware\CommandBus\CommandHandlerInterface;
use Webware\CommandBus\CommandInterface;
use Webware\Mailer\MailerInterface;

final readonly class SendEmailCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private MailerInterface $mailer,
    ) {}

    /**
     * @param CommandInterface&SendEmailCommand $command
     */
    #[Override]
    public function handle(CommandInterface $command): CommandResultInterface
    {
        try {
            $adapter = $this->mailer->getAdapter();
            if ($adapter === null) {
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
                CommandStatus::Failure,
                $e->getMessage(),
            );
        }

        return new CommandResult(
            $command,
            CommandStatus::Success,
            'Email sent successfully',
        );
    }
}
