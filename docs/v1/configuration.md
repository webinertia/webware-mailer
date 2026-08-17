# Configuration Reference

`Webware\Mailer\ConfigProvider` publishes four configuration groups:

| Key | Contents |
|---|---|
| `dependencies` | Aliases and factories for the mailer services |
| `templates` | Template path list under `paths.mail` |
| `Webware\MessageBus\MessageBusInterface::class` | `command_map` mapping `SendEmailCommand` to its handler |
| `Webware\Mailer\Adapter\AdapterInterface::class` | Adapter options (see below) |
| `Webware\Mailer\Adapter\MessageInterface::class` | Message options (currently empty) |

## Adapter Options

Adapter options live under the `AdapterInterface` key:

```php
use Webware\Mailer\Adapter\AdapterInterface;

return [
    AdapterInterface::class => [
        'enableExceptions' => true,
        'useSmtp'         => true,
        'host'            => 'smtp.example.com',
        'port'            => 587,
        'smtp_auth'       => true,
        'username'        => 'user',
        'password'        => 'secret',
        'charset'         => 'UTF-8',
        'encoding'        => 'base64',
        'timeout'         => 30,
        'smtp_secure'     => 'tls',
    ],
];
```

| Key | Type | Default | Description |
|---|---|---|---|
| `enableExceptions` | `bool` | `true` | Pass exceptions from PHPMailer through to callers |
| `useSmtp` | `bool` | `false` | Use SMTP transport; otherwise PHP `mail()` |
| `host` | `string` | `''` | SMTP host (only used when `useSmtp` is `true`) |
| `port` | `int` | `25` | SMTP port |
| `smtp_auth` | `bool` | `false` | Enable SMTP authentication |
| `username` | `string` | `''` | SMTP username |
| `password` | `string` | `''` | SMTP password |
| `charset` | `string` | `UTF-8` | Message character set |
| `encoding` | `string` | `base64` | Content transfer encoding |
| `timeout` | `int` | `30` | SMTP connection timeout in seconds |
| `smtp_secure` | `string` | `''` | `tls` or `ssl`; empty for none |

If the `AdapterInterface` configuration entry is missing entirely, creating the
adapter throws `Laminas\ServiceManager\Exception\ServiceNotCreatedException`.

## Templates

Templates are registered under the `templates` key:

```php
[
    'templates' => [
        'paths' => [
            'mail' => [__DIR__ . '/../templates/'],
        ],
    ],
]
```

## Command Map

`SendEmailCommand` is mapped to `SendEmailCommandHandler` automatically:

```php
use Webware\Mailer\CommandBus\SendEmailCommand;
use Webware\Mailer\CommandBus\SendEmailCommandHandler;
use Webware\MessageBus\ConfigProvider as BusProvider;
use Webware\MessageBus\MessageBusInterface;

return [
    MessageBusInterface::class => [
        BusProvider::COMMAND_MAP_KEY => [
            SendEmailCommand::class => SendEmailCommandHandler::class,
        ],
    ],
];
```

Override the `command_map` entry in your own config to replace the handler.
