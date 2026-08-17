# Mailer

`Webware\Mailer\Mailer` is the service consumers interact with. It holds an
adapter and delegates sending to it.

```php
namespace Webware\Mailer;

interface MailerInterface
{
    public function getAdapter(): ?Adapter\AdapterInterface;

    public function send(): bool;

    public function setAdapter(Adapter\AdapterInterface $adapter): self;
}
```

- `getAdapter()` returns `null` until an adapter is set.
- `setAdapter()` replaces the adapter and returns `$this`.
- `send()` throws `RuntimeException` when no adapter is configured.

## Usage

Retrieve `MailerInterface` from the container (an alias for `Mailer` is
registered by `ConfigProvider`):

```php
use Webware\Mailer\MailerInterface;

/** @var MailerInterface $mailer */
$mailer = $container->get(MailerInterface::class);

$adapter = $mailer->getAdapter();

if (null !== $adapter) {
    $adapter->to('recipient@example.com')
        ->from('sender@example.com')
        ->subject('Hello')
        ->body('Message body');

    $mailer->send();
}
```

## Mailer-Aware Services

`MailerAwareInterface` and `MailerAwareInterfaceTrait` let services request the
mailer:

```php
use Webware\Mailer\MailerAwareInterface;
use Webware\Mailer\MailerAwareInterfaceTrait;

final class OrderNotificationService implements MailerAwareInterface
{
    use MailerAwareInterfaceTrait;

    public function notify(): void
    {
        $adapter = $this->getMailer()->getAdapter();

        if (null === $adapter) {
            return;
        }

        $adapter->to('customer@example.com')
            ->subject('Order confirmed');

        $this->getMailer()->send();
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
