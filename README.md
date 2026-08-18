# webware/webware-mailer

[![PHP Version](https://img.shields.io/packagist/php-v/webware/webware-mailer)](https://packagist.org/packages/webware/webware-mailer)
[![Latest Version](https://img.shields.io/packagist/v/webware/webware-mailer)](https://packagist.org/packages/webware/webware-mailer)
[![License](https://img.shields.io/github/license/webinertia/webware-mailer)](LICENSE)
[![Continuous Integration](https://github.com/webinertia/webware-mailer/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/webinertia/webware-mailer/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/webinertia/webware-mailer/graph/badge.svg)](https://codecov.io/gh/webinertia/webware-mailer)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fwebinertia%2Fwebware-mailer%2F1.0.x)](https://dashboard.stryker-mutator.io/reports/github.com/webinertia/webware-mailer/1.0.x)

Php Mail abstraction for a variety of php mailer libraries for Mezzio
applications. Ships with a [PHPMailer](https://github.com/PHPMailer/PHPMailer)
adapter, PSR-15 middleware, and a `webware/message-bus` command for sending
email.

## Documentation

- [Installation](docs/v1/installation.md)
- [Configuration Reference](docs/v1/configuration.md)
- [Adapters](docs/v1/adapters.md)
- [Mailer](docs/v1/mailer.md)
- [Middleware](docs/v1/middleware.md)
- [Command Bus Integration](docs/v1/command-bus.md)
- [Events](docs/v1/events.md)

## Quick Start

```bash
composer require webware/webware-mailer
```

`laminas/laminas-component-installer` injects `Webware\Mailer\ConfigProvider`
automatically. Then send a message:

```php
use Webware\Mailer\Container\MailerFactory;

/** @var Psr\Container\ContainerInterface $container */
$mailer = (new MailerFactory())($container);

$adapter = $mailer->getAdapter();

if (null !== $adapter) {
    $adapter->to('recipient@example.com')
        ->from('sender@example.com')
        ->subject('Hello')
        ->body('Message body');

    $mailer->send();
}
```

To send through SMTP instead of PHP's `mail()`, see the
[Configuration Reference](docs/v1/configuration.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).
