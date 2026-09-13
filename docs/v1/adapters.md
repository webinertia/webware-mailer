# Adapters

Adapters translate `Webware\Mailer\Adapter\AdapterInterface` calls to a
concrete mailer library. The package ships with a
[PHPMailer](https://github.com/PHPMailer/PHPMailer) adapter.

## Interfaces

`AdapterInterface` extends `MessageInterface`. It adds the transport controls and
contracts the settings the implementation was configured with as read-only
properties, so consumers read typed values instead of the raw configuration array:

```php
namespace Webware\Mailer\Adapter;

interface AdapterInterface extends MessageInterface
{
    public bool $enableExceptions { get; }

    public string $charset { get; }

    public string $encoding { get; }

    public string $from { get; }

    public string $host { get; }

    public string $password { get; }

    public int $port { get; }

    public bool $smtpAuth { get; }

    public string $smtpSecure { get; }

    public int $timeout { get; }

    public string $username { get; }

    public bool $useSmtp { get; }

    public function isMail(): self;

    public function isSmtp(): self;

    public function send(): bool;
}
```

These are the minimum an implementation needs. An implementation may publish
additional optional properties of its own.

`MessageInterface` defines the message-building API. Every method returns a **new
instance** over a cloned transport, so a partially configured message can be shared
without one caller's changes reaching another:

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
message is simply a new adapter, and recipients, headers and attachments can never
carry over from one send to the next.

## PHPMailer Adapter

`Webware\Mailer\Adapter\PhpMailer` wraps
`PHPMailer\PHPMailer\PHPMailer`. Method mapping:

| Adapter method | PHPMailer call |
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

Each `with*()` clones the wrapped `PHPMailer` instance before applying the change;
the adapter the caller already holds is untouched. The configuration properties are
set once by `Webware\Mailer\Container\PhpMailerFactory` and are `private(set)`.

Methods that delegate to PHPMailer functions may throw
`PHPMailer\PHPMailer\Exception` when `enableExceptions` is enabled.
