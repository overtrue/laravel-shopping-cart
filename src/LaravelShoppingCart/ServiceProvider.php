<?php

/*
 * This file is part of the overtrue/laravel-shopping-cart.
 *
 * (c) 2016 overtrue <i@overtrue.me>
 */

namespace Overtrue\LaravelShoppingCart;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

/**
 * Service provider for Laravel.
 */
class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Boot the provider.
     */
    public function boot()
    {
        //
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->singleton([Cart::class => 'cart'], function ($app) {
            return new Cart($app['session'], $app['events']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [Cart::class, 'cart'];
    }
}
