# Adapters

Adapters translate `Webware\Mailer\Adapter\AdapterInterface` calls to a
concrete mailer library. The package ships adapters for
[PHPMailer](https://github.com/PHPMailer/PHPMailer) and
[Symfony Mailer](https://symfony.com/doc/current/mailer.html). Neither library is
a hard requirement, so install whichever one you use.

## Wiring an adapter

`AdapterInterface` is the seam the rest of the package resolves — `Mailer` takes
nothing else — so mapping it to an implementation is the only wiring required.
Both adapters are registered as services by `ConfigProvider`; point the interface
at the one you want:

```php
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\Adapter\SymfonyMailer; // or PhpMailer

return [
    'dependencies' => [
        'aliases' => [
            AdapterInterface::class => SymfonyMailer::class,
        ],
    ],
];
```

The mapping is resolved at runtime, so it can differ per environment, and it can
name any implementation of `AdapterInterface` — including one that lives outside
this package.

Adapter options are read from the `AdapterInterface` configuration section, and
the keys are that implementation's own; see [Configuration](configuration.md).

## Interfaces

`AdapterInterface` is the transport contract: it carries the settings the
implementation was configured with as read-only properties, and it sends one
message. Consumers read typed values instead of the raw configuration array:

```php
namespace Webware\Mailer\Adapter;

interface AdapterInterface
{
    public bool $enableExceptions { get; }

    public string $host { get; }

    public string $password { get; }

    public int $port { get; }

    public bool $smtpAuth { get; }

    public string $smtpSecure { get; }

    public int $timeout { get; }

    public string $username { get; }

    public function isMail(): bool;

    public function isSmtp(): bool;

    public function send(MessageInterface $message): bool;
}
```

These are the minimum an implementation needs. An implementation may publish
additional optional properties of its own.

Message content is not part of this contract. An adapter is built once from its
transport configuration and holds that transport for its lifetime; the message
arrives at send time. Transport settings survive from one send to the next, the
message does not.

`MessageInterface` defines the message-building API. Every method returns a **new
instance**, so a partially configured message can be shared without one caller's
changes reaching another:

| Method | Description |
|---|---|
| `withAltBody(string $altBody): static` | Set the plain-text alternative body |
| `withAttachment(string $path, string $name = '', string $mimeType = ''): static` | Attach a file |
| `withAttachmentFromString(string $content, string $name, string $mimeType = ''): static` | Attach from a string |
| `withBcc(string $email, string $name = ''): static` | Add a BCC recipient |
| `withBody(string $body): static` | Set the message body |
| `withCc(string $email, string $name = ''): static` | Add a CC recipient |
| `withCharset(string $charset): static` | Set the character set |
| `withEncoding(string $encoding): static` | Set the transfer encoding |
| `withFrom(string $email, string $name = ''): static` | Set the sender |
| `withHeader(string $name, string $value): static` | Add a custom header |
| `withHtml(bool $flag = true): static` | Toggle HTML content type |
| `withReplyTo(string $email, string $name = ''): static` | Add a reply-to address |
| `withSubject(string $subject): static` | Set the subject |
| `withTo(string $email, string $name = ''): static` | Add a recipient |

There is no reset method: because every call returns a fresh instance, a cleared
message is simply a new `Message`. Each call builds on the state of the instance it
was called on, so chaining `with*()` calls accumulates recipients, headers and
attachments; nothing carries over between separate message instances. The adapter
clears its transport's message state before applying each message, so two sends
through the same adapter do not share it either.

## PHPMailer Adapter

`Webware\Mailer\Adapter\PhpMailer` wraps
`PHPMailer\PHPMailer\PHPMailer`. Method mapping:

| Message method | PHPMailer call |
|---|---|
| `withAltBody()` | `AltBody` property |
| `withAttachment()` | `addAttachment(..., encoding: 'base64', ...)` |
| `withAttachmentFromString()` | `addStringAttachment(..., encoding: 'base64', ...)` |
| `withBcc()` | `addBCC()` |
| `withBody()` | `Body` property |
| `withCc()` | `addCC()` |
| `withCharset()` | `CharSet` property |
| `withEncoding()` | `Encoding` property |
| `withFrom()` | `setFrom()` |
| `withHeader()` | `addCustomHeader()` |
| `withHtml()` | `isHTML()` |
| `withReplyTo()` | `addReplyTo()` |
| `withSubject()` | `Subject` property |
| `withTo()` | `addAddress()` |
| `isMail()` | `isMail()` |
| `isSmtp()` | `isSMTP()` |
| `send()` | `send()` |

Each `with*()` returns a new adapter that builds its own `PHPMailer` transport from
the state it carries before applying the change; the adapter the caller already
holds is untouched. Nothing is cloned or mutated in place, so a transport never
accumulates state across instances.

The constructor is private. Build an adapter either through
`Webware\Mailer\Container\PhpMailerFactory` (the container path) or
`PhpMailer::fromConfig()`, which maps this implementation's own configuration shape
and holds its defaults in one place. The configuration properties are `private(set)`.

Methods that delegate to PHPMailer functions may throw
`PHPMailer\PHPMailer\Exception` when `enableExceptions` is enabled.
