<?php

namespace EolabsIo\AmazonSpApiThrottlingMiddleware;

use EolabsIo\AmazonSpApiThrottlingMiddleware\AmazonSpApiThrottlingMiddleware;
use Illuminate\Support\ServiceProvider;

class AmazonSpApiThrottlingMiddlewareServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'amazon-sp-api-throttling-middleware');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'amazon-sp-api-throttling-middleware');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('amazon-sp-api-throttling-middleware.php'),
            ], 'config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/amazon-sp-api-throttling-middleware'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/amazon-sp-api-throttling-middleware'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/amazon-sp-api-throttling-middleware'),
            ], 'lang');*/

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'amazon-sp-api-throttling-middleware');

        // Register the main class to use with the facade
        $this->app->singleton('amazon-sp-api-throttling-middleware', function () {
            return new AmazonSpApiThrottlingMiddleware;
        });
    }
}
