# Events

`Webware\Mailer\Event\MessageEvent` extends
`Webware\MessageBus\Event\Event` from `webware/messagebus-event`.

```php
final class MessageEvent extends Event
{
    final public const string EVENT_EMAIL_MESSAGE = 'emailMessage';
}
```

`EVENT_EMAIL_MESSAGE` is the event name consumers use when subscribing to
email-related events.

## Event-Aware Commands

`SendEmailCommand` implements
`Webware\MessageBus\Event\EventAwareInterface`:

- `getEvent(): MessageEvent` returns the message event.
- `setEvent(EventInterface $event): void` replaces the event and throws
  `InvalidArgumentException` when the event is not a `MessageEvent`.

```php
use Webware\Mailer\CommandBus\SendEmailCommand;
use Webware\Mailer\Event\MessageEvent;

$event = new MessageEvent();

$command = new SendEmailCommand(
    'recipient@example.com',
    'sender@example.com',
    'Subject',
    'Body',
    $event,
);

$command->getEvent() === $event; // true
```

`MessageEvent` inherits the full `Event` API from `webware/messagebus-event`
(`getName()`, `getParam()`, `setParam()`, `getParams()`, `setParams()`,
`getTarget()`, `setTarget()`).
