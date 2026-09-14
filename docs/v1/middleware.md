# Middleware

`Webware\Mailer\Http\Middleware\MailerMiddleware` attaches the `Mailer` to the
request as an attribute. It carries no configuration of its own — the adapter's
settings, including its default sender, come from the adapter contract.

## Wiring

Pipe the middleware:

```php
// config/pipeline.php
$app->pipe(\Webware\Mailer\Http\Middleware\MailerMiddleware::class);
```

The factory resolves the mailer from the container under `MailerInterface::class`;
there is nothing to configure for this middleware.

The sender address is configuration on the adapter instead, and is applied once when
the adapter is built:

```php
use Webware\Mailer\Adapter\AdapterInterface;

return [
    AdapterInterface::class => [
        'from' => 'sender@example.com',
    ],
];
```

## Behavior

For each request, the middleware:

1. Attaches the `Mailer` to the request under the `MailerInterface::class`
   attribute key.
2. Delegates to the next handler.

Downstream middleware and handlers can read the mailer:

```php
use Webware\Mailer\MailerInterface;

$mailer = $request->getAttribute(MailerInterface::class);
```

Read the adapter's typed transport settings from the adapter itself:

```php
$adapter = $mailer->getAdapter();

$host = $adapter->host;   // string, from the adapter config section
```

Message content — sender included — lives on the message instead:

```php
$from = (new Message())->withFrom('sender@example.com')->from;
```
