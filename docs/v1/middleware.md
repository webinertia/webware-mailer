# Middleware

`Webware\Mailer\Middleware\MailerMiddleware` prepares the adapter during a
request: it sets the configured sender address and attaches the `Mailer` to
the request as an attribute.

## Wiring

The factory reads settings from the application config:

```php
use Webware\Mailer\Adapter\AdapterInterface;
use Webware\Mailer\ConfigProvider;

return [
    ConfigProvider::class => [
        AdapterInterface::class => [
            'from' => 'sender@example.com',
        ],
    ],
];
```

Pipe the middleware:

```php
// config/pipeline.php
$app->pipe(\Webware\Mailer\Middleware\MailerMiddleware::class);
```

## Behavior

For each request, the middleware:

1. Retrieves the adapter from the `Mailer`.
2. Calls `from()` on the adapter when the `from` config key is a string.
3. Attaches the `Mailer` to the request under the `MailerInterface::class`
   attribute key.
4. Delegates to the next handler.

Downstream middleware and handlers can read the mailer:

```php
use Webware\Mailer\MailerInterface;

$mailer = $request->getAttribute(MailerInterface::class);
```

If the `ConfigProvider` entry or the adapter settings are not arrays, the
factory throws `RuntimeException`.
