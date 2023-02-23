# OpenTelemetry for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/plunkettscott/laravel-otel.svg?style=flat-square)](https://packagist.org/packages/plunkettscott/laravel-otel)
[![Total Downloads](https://img.shields.io/packagist/dt/plunkettscott/laravel-otel.svg?style=flat-square)](https://packagist.org/packages/plunkettscott/laravel-otel)
![GitHub Actions](https://github.com/plunkettscott/laravel-otel/actions/workflows/main.yml/badge.svg)

This package provides an OpenTelemetry integration for Laravel applications. It is based on the [OpenTelemetry PHP](https://github.com/opentelemetry/opentelemetry-php) project and
provides instrumentation for a number of Laravel components.

## Important Note

This package currently relies on a beta release of the OpenTelemetry PHP project.

We will keep the OpenTelemetry PHP dependency up-to-date as new releases are made available. However,
we cannot guarantee that this package will work with future versions of OpenTelemetry PHP without breaking
changes.

## Watchers

This package is currently in development and contains the following Watchers:

- [x] Incoming HTTP Requests
- [x] HTTP Client Requests
- [x] Database Queries
- [x] Redis Commands
- [ ] Queue Jobs
- [ ] Cache Commands
- [ ] View Rendering
- [x] Exceptions
- [x] Log Messages

## Requirements

- PHP 8.2+
- Laravel 10.0+

## Installation

You can install the package via composer:

```bash
composer require plunkettscott/laravel-otel
```

## Usage

1. Install the package

```bash
composer require plunkettscott/laravel-otel
```

2. Execute the `otel:install` command

```bash
php artisan otel:install
```

3. Configure the methods in `app/Providers/OtelServiceProvider.php` to suit your needs
4. Configure the watchers in `config/otel.php` to suit your needs

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email oss (at) scottplunkett.com instead of using the issue tracker.

## Credits

-   [Scott Plunkett](https://github.com/plunkettscott)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
