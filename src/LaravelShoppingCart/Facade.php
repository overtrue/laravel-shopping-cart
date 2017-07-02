<?php

/*
 * This file is part of the overtrue/laravel-shopping-cart.
 *
 * (c) 2016 overtrue <i@overtrue.me>
 */

namespace Overtrue\LaravelShoppingCart;

use Illuminate\Support\Facades\Facade as LaravelFacade;

/**
 * Facade for Laravel.
 */
class Facade extends LaravelFacade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Cart::class;
    }
}
