<?php

namespace JustBetter\ExactClient;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use JustBetter\ExactClient\Actions\DetermineRateLimited;
use JustBetter\ExactClient\Client\Exact;
use JustBetter\ExactClient\Commands\ListDivisionsCommand;
use JustBetter\ExactClient\Commands\RefreshTokenCommand;
use JustBetter\ExactClient\Events\ExactResponseEvent;
use JustBetter\ExactClient\Listeners\StoreRateLimitsListener;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this
            ->registerActions()
            ->registerConfig();
    }

    protected function registerActions(): static
    {
        app()->bind(Exact::class, function (): Exact {
            return new Exact(
                config()->string('exact.division')
            );
        });

        DetermineRateLimited::bind();

        return $this;
    }

    protected function registerConfig(): static
    {
        $this->mergeConfigFrom(__DIR__.'/../config/exact.php', 'exact');

        return $this;
    }

    public function boot(): void
    {
        $this
            ->bootConfig()
            ->bootEvents()
            ->bootMigrations()
            ->bootCommands()
            ->bootRoutes();
    }

    protected function bootConfig(): static
    {
        $this->publishes([
            __DIR__.'/../config/exact.php' => config_path('exact.php'),
        ], 'config');

        return $this;
    }

    protected function bootEvents(): static
    {
        Event::listen(ExactResponseEvent::class, StoreRateLimitsListener::class);

        return $this;
    }

    protected function bootMigrations(): static
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        return $this;
    }

    protected function bootCommands(): static
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ListDivisionsCommand::class,
                RefreshTokenCommand::class,
            ]);
        }

        return $this;
    }

    protected function bootRoutes(): static
    {
        if (! app()->routesAreCached()) {
            Route::prefix(config()->string('exact.prefix'))
                ->middleware(config()->array('exact.middleware'))
                ->group(fn () => $this->loadRoutesFrom(__DIR__.'/../routes/web.php'));
        }

        return $this;
    }
}
