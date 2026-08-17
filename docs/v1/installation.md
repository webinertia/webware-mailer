# Installation

## Requirements

- PHP 8.4.1 or later
- A Mezzio application (PSR-11 container, PSR-15 middleware)

## Composer

```bash
composer require webware/webware-mailer
```

`laminas/laminas-component-installer` will prompt you to inject
`Webware\Mailer\ConfigProvider` into your application's config aggregator.
Accept the prompt or add it manually:

```php
// config/config.php
new Webware\Mailer\ConfigProvider(),
```

## Message Bus (Optional)

The `SendEmailCommand` handler integrates with
[`webware/message-bus`](https://github.com/webinertia/message-bus). To use it,
install the message bus packages in your application:

```bash
composer require webware/message-bus webware/messagebus-event
```

The command-to-handler mapping is registered automatically through
`ConfigProvider`. See [Command Bus Integration](command-bus.md).
