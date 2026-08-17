# Adapters

Adapters translate `Webware\Mailer\Adapter\AdapterInterface` calls to a
concrete mailer library. The package ships with a
[PHPMailer](https://github.com/PHPMailer/PHPMailer) adapter.

## Interfaces

`AdapterInterface` extends `MessageInterface` and adds transport controls:

```php
namespace Webware\Mailer\Adapter;

interface AdapterInterface extends MessageInterface
{
    public function isMail(): self;

    public function isSmtp(): self;

    public function send(): bool;
}
```

`MessageInterface` defines the fluent message API. Every method returns `self`
for chaining:

| Method | Description |
|---|---|
| `addHeader(string $name, string $value)` | Add a custom header |
| `altBody(string $altBody)` | Set the plain-text alternative body |
| `attach(string $path, string $name = '', string $mimeType = '')` | Attach a file |
| `attachFromString(string $content, string $name, string $mimeType = '')` | Attach from a string |
| `bcc(string $email, string $name = '')` | Add a BCC recipient |
| `body(string $body)` | Set the message body |
| `cc(string $email, string $name = '')` | Add a CC recipient |
| `charset(string $charset)` | Set the character set |
| `encoding(string $encoding)` | Set the transfer encoding |
| `from(string $email, string $name = '')` | Set the sender |
| `isHtml(bool $flag = true)` | Toggle HTML content type |
| `replyTo(string $email, string $name = '')` | Add a reply-to address |
| `reset()` | Clear all recipients, attachments, headers, subject and bodies |
| `subject(string $subject)` | Set the subject |
| `to(string $email, string $name = '')` | Add a recipient |

## PHPMailer Adapter

`Webware\Mailer\Adapter\PhpMailer` wraps
`PHPMailer\PHPMailer\PHPMailer`. Method mapping:

| Adapter method | PHPMailer call |
|---|---|
| `addHeader()` | `addCustomHeader()` |
| `altBody()` | `AltBody` property |
| `attach()` | `addAttachment(..., encoding: 'base64', ...)` |
| `attachFromString()` | `addStringAttachment(..., encoding: 'base64', ...)` |
| `bcc()` | `addBCC()` |
| `body()` | `Body` property |
| `cc()` | `addCC()` |
| `charset()` | `CharSet` property |
| `encoding()` | `Encoding` property |
| `from()` | `setFrom()` |
| `isHtml()` | `isHTML()` |
| `isMail()` | `isMail()` |
| `isSmtp()` | `isSMTP()` |
| `replyTo()` | `addReplyTo()` |
| `reset()` | `clearAddresses()`, `clearCCs()`, `clearBCCs()`, `clearReplyTos()`, `clearAttachments()`, `clearCustomHeaders()`, plus empty `Subject`, `Body`, `AltBody` |
| `send()` | `send()` |
| `subject()` | `Subject` property |
| `to()` | `addAddress()` |

Methods that delegate to PHPMailer functions may throw
`PHPMailer\PHPMailer\Exception` when `enableExceptions` is enabled.
