# Configuration Reference

`Webware\Mailer\ConfigProvider` publishes four configuration groups:

| Key | Contents |
|---|---|
| `dependencies` | Aliases and factories for the mailer services |
| `templates` | Template path list under `paths.mail` |
| `Webware\MessageBus\MessageBusInterface::class` | `command_map` mapping `SendEmailCommand` to its handler |
| `Webware\Mailer\Adapter\AdapterInterface::class` | Adapter options (see below) |

## Adapter Options

Adapter options live under the single `AdapterInterface` key — this is the only
path the adapter factory reads:

```php
use Webware\Mailer\Adapter\AdapterInterface;

return [
    AdapterInterface::class => [
        'enableExceptions' => true,
        'useSmtp'          => true,
        'host'             => 'smtp.example.com',
        'port'             => 587,
        'smtpAuth'         => true,
        'username'         => 'user',
        'password'         => 'secret',
        'smtpSecure'       => 'tls',
        'charset'          => 'UTF-8',
        'encoding'         => 'base64',
        'timeout'          => 30,
        'from'             => 'sender@example.com',
        'fromName'         => 'Example Sender',
    ],
];
```

| Key | Type | Description |
|---|---|---|
| `enableExceptions` | `bool` | Pass exceptions from the transport through to callers |
| `useSmtp` | `bool` | Use SMTP transport; otherwise PHP `mail()` |
| `host` | non-empty `string` | SMTP host |
| `port` | `int<1, 65535>` | SMTP port |
| `smtpAuth` | `bool` | Enable SMTP authentication |
| `username` | `string` | SMTP username |
| `password` | `string` | SMTP password |
| `smtpSecure` | `''`, `'tls'` or `'ssl'` | SMTP encryption |
| `charset` | non-empty `string` | Message character set |
| `encoding` | non-empty `string` | Content transfer encoding |
| `timeout` | `positive-int` | SMTP connection timeout in seconds |
| `from` | non-empty `string` | Sender address |
| `fromName` | non-empty `string` | Sender display name |

Every key is optional. `ConfigProvider` publishes `enableExceptions` and `useSmtp`;
the remaining keys are the host application's to supply. `PhpMailer::fromConfig()`
resolves each key against its own defaults, which mirror PHPMailer's own
(`localhost`, port 25, timeout 300, `iso-8859-1`, `8bit`), so omitting a key leaves
the transport behaving exactly as PHPMailer would on its own. The shape is declared
per implementation as a `@type` alias on the adapter (`PhpMailerConfig` on
`PhpMailer`) and imported by `ConfigProvider`.

Once the adapter is built, read the values from it as typed properties
(`$adapter->host`, `$adapter->from`, `$adapter->fromName`, ...) rather than from
the raw array — the adapter reports the configuration in force, defaults included.

If the `AdapterInterface` configuration entry is missing, not an array, or empty,
creating the adapter throws `Laminas\ServiceManager\Exception\ServiceNotCreatedException`.

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
use Webware\Mailer\Command\SendEmailCommand;
use Webware\Mailer\CommandHandler\SendEmailCommandHandler;
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
