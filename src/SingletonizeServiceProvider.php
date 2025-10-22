<?php

namespace Codex\LaravelSingletonize;

use Illuminate\Support\ServiceProvider;

class SingletonizeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-singletonize.php', 'laravel-singletonize');

        $this->app->singleton(Singletonizer::class, function ($app) {
            $config = $app['config'] ?? null;

            $enabled = true;

            if ($config && method_exists($config, 'get')) {
                $enabled = (bool) $config->get('laravel-singletonize.enabled', true);
            }

            return new Singletonizer($app, $enabled);
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/laravel-singletonize.php' => $this->configPath('laravel-singletonize.php'),
        ], 'config');

        $this->app->make(Singletonizer::class)->boot();
    }

    protected function configPath(string $name): string
    {
        if (function_exists('config_path')) {
            return config_path($name);
        }

        return $this->app->basePath('config'.DIRECTORY_SEPARATOR.$name);
    }
}
