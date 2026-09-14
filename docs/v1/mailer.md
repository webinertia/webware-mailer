# Mailer

`Webware\Mailer\Mailer` is the service consumers interact with. It holds an
adapter and delegates sending to it.

```php
namespace Webware\Mailer;

interface MailerInterface
{
    public function getAdapter(): Adapter\AdapterInterface;

    public function send(Adapter\MessageInterface $message): bool;
}
```

- `getAdapter()` returns the adapter the mailer was built with. The adapter is
  supplied by the factory, so it is never absent.
- `send()` builds nothing: it hands the message you give it to the adapter.
- Nothing about a message is stored here, so two sends cannot bleed into one
  another — see [Adapters](adapters.md).

## Usage

Retrieve `MailerInterface` from the container (an alias for `Mailer` is
registered by `ConfigProvider`):

```php
use Webware\Mailer\MailerInterface;
use Webware\Mailer\Message;

/** @var MailerInterface $mailer */
$mailer = $container->get(MailerInterface::class);

$message = new Message()->withTo('recipient@example.com')
    ->withFrom('sender@example.com')
    ->withSubject('Hello')
    ->withBody('Message body');

$mailer->send($message);
```

## Mailer-Aware Services

`MailerAwareInterface` and `MailerAwareInterfaceTrait` let services request the
mailer:

```php
use Webware\Mailer\MailerAwareInterface;
use Webware\Mailer\MailerAwareInterfaceTrait;
use Webware\Mailer\Message;

final class OrderNotificationService implements MailerAwareInterface
{
    use MailerAwareInterfaceTrait;

    public function notify(): void
    {
        $message = new Message()->withTo('customer@example.com')
            ->withSubject('Order confirmed');

        $this->getMailer()->send($message);
    }
}
```

Register the delegator so the mailer is injected automatically:

```php
// config/autoload/dependencies.global.php
use App\Service\OrderNotificationService;
use Webware\Mailer\MailerAwareDelegator;

return [
    'dependencies' => [
        'delegators' => [
            OrderNotificationService::class => [
                MailerAwareDelegator::class,
            ],
        ],
    ],
];
```

Services that do not implement `MailerAwareInterface` are passed through
untouched.
