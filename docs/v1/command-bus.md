# Command Bus Integration

The package provides a `webware/message-bus` command for sending email:
`Webware\Mailer\CommandBus\SendEmailCommand`. Its handler composes the message
on the configured adapter and sends it.

## Mapping

`ConfigProvider` registers the command map automatically:

```php
use Webware\Mailer\CommandBus\SendEmailCommand;
use Webware\Mailer\CommandBus\SendEmailCommandHandler;
use Webware\MessageBus\MessageBusInterface;

$commandMap = [
    SendEmailCommand::class => SendEmailCommandHandler::class,
];
// registered under MessageBusInterface::class => ['command_map' => ...]
```

## Sending Email

```php
use Webware\Mailer\CommandBus\SendEmailCommand;
use Webware\Mailer\Event\MessageEvent;
use Webware\MessageBus\MessageBusInterface;

/** @var MessageBusInterface $bus */
$bus = $container->get(MessageBusInterface::class);

$command = new SendEmailCommand(
    'recipient@example.com',
    'sender@example.com',
    'Subject',
    'Body',
    new MessageEvent(),
);

$result = $bus->handle($command);
```

## Result

`handle()` returns a `Webware\MessageBus\Command\CommandResultInterface`:

| Method | Description |
|---|---|
| `getCommand()` | The command that was handled |
| `getStatus()` | `Webware\MessageBus\MessageStatus::Success` or `MessageStatus::Failure` |
| `getResult()` | `Email sent successfully` on success; the exception message on failure |

```php
use Webware\MessageBus\MessageStatus;

if ($result->getStatus() === MessageStatus::Success) {
    // sent
}
```

When no adapter is configured the handler returns `Failure` with the message
`No adapter configured on Mailer instance.`
