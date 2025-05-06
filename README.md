<a href="https://github.com/justbetter/laravel-exact-client" title="Exact Client">
    <img src="./art/banner.svg" alt="Banner">
</a>

# Laravel Exact Client

<p>
    <a href="https://github.com/justbetter/laravel-exact-client"><img src="https://img.shields.io/github/actions/workflow/status/justbetter/laravel-exact-client/tests.yml?label=tests&style=flat-square" alt="Tests"></a>
    <a href="https://github.com/justbetter/laravel-exact-client"><img src="https://img.shields.io/github/actions/workflow/status/justbetter/laravel-exact-client/coverage.yml?label=coverage&style=flat-square" alt="Coverage"></a>
    <a href="https://github.com/justbetter/laravel-exact-client"><img src="https://img.shields.io/github/actions/workflow/status/justbetter/laravel-exact-client/analyse.yml?label=analysis&style=flat-square" alt="Analysis"></a>
    <a href="https://github.com/justbetter/laravel-exact-client"><img src="https://img.shields.io/packagist/dt/justbetter/laravel-exact-client?color=blue&style=flat-square" alt="Total downloads"></a>
</p>

A client to communicate with Exact from your Laravel application.

## Installation

Install the composer package.

```bash
composer require justbetter/laravel-exact-client
```

## Setup

Publish the configuration of the package.

```bash
php artisan vendor:publish --provider="JustBetter\ExactClient\ServiceProvider" --tag=config
```

Run the migrations.

```bash
php artisan migrate
```

Add the following keys to your `.env` file:

```dotenv
EXACT_CLIENT_ID=
EXACT_CLIENT_SECRET=
EXACT_DIVISION=
```

Out of the box, the connection is called `default`.

### Middleware

By default, no middleware has been added to authorize with Exact. Update the `middleware` in your configuration to add proper authentication and authorization.

## Exact Apps

You have to create a new app in Exact Online. Make sure your account has the necessary permissions, otherwise you will be redirected back to the login page.

1. Open the [login](https://www.exact.com/login) page.
2. Click on the login button in the section "Exact Online App Store"
3. Register a new [app](https://apps.exactonline.com).

Make sure the redirect URL is the callback URL of the application. This must be an HTTPS address.

```
https://localhost/exact/callback/default
```

Divisions in the configuration file must be unique across all connections.

## Initiate Authentication

In order to initiate the authentication process with Exact, open the following link.

```
https://localhost/exact/authorize/default
```

After finishing the process, you can check your connection by requesting all available divisions.

```bash
php artisan exact:list-divisions default
```

Tokens are stored in the database.

## Retaining Access

Making calls to Exact will make sure the tokens remain valid. The package automatically refreshes the tokens when required. If you are not regularly making calls to Exact, you should add the command below to your scheduler. Otherwise, the refresh token may expire and you'll have to authenticate with Exact again. A refresh token is valid for [30 days](https://support.exactonline.com/community/s/knowledge-base#All-All-DNO-Content-oauth-eol-oauth-devstep3).

```php
$schedule->command(\JustBetter\ExactClient\Commands\RefreshTokenCommand::class, [
    'connection' => 'default',
])->weekly();
```

## Rate Limits

Exact is known for their strict rate limiting. It's generally recommended to distribute a load over a longer period of time. To prevent unnessecary failures further, this package includes a `RateLimitMiddleware` which is a [job middleware](https://laravel.com/docs/12.x/queues#job-middleware) to automatically release jobs back on the queue if a rate limit has been exceeded.

```php
use JustBetter\ExactClient\Jobs\Middleware\RateLimitMiddleware;

public function middleware(): array
{
    return [
        RateLimitMiddleware::division('default'),
    ];
}
```

Rate limits are stored in the database.

## Quality

To ensure the quality of this package, run the following command:

```bash
composer quality
```

This will execute three tasks:

1. Makes sure all tests are passed
2. Checks for any issues using static code analysis
3. Checks if the code is correctly formatted

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Vincent Boon](https://github.com/VincentBean)
- [Ramon Rietdijk](https://github.com/ramonrietdijk)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

<a href="https://justbetter.nl" title="JustBetter">
    <img src="./art/footer.svg" alt="JustBetter logo">
</a>
