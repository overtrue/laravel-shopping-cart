<?php

/*
 * This file is part of the overtrue/laravel-shopping-cart.
 *
 * (c) 2016 overtrue <i@overtrue.me>
 */

namespace Overtrue\LaravelShoppingCart;

use Illuminate\Support\Collection;

/**
 * Shopping cart item.
 *
 * @property int|string $id
 * @property string     $__raw_id
 */
class Item extends Collection
{
    /**
     * The Eloquent model a cart is associated with.
     *
     * @var string
     */
    protected $model;

    /**
     * Magic accessor.
     *
     * @param string $property Property name.
     *
     * @return mixed
     */
    public function __get($property)
    {
        if ($this->has($property)) {
            return $this->get($property);
        }

        if (!$this->get('__model')) {
            return;
        }

        $model = $this->get('__model');
        $class = explode('\\', $model);

        if (strtolower(end($class)) === $property) {
            $model = new $model();

            return $model->find($this->id);
        }

        return;
    }

    /**
     * Return the raw ID of item.
     *
     * @return string
     */
    public function rawId()
    {
        return $this->__raw_id;
    }
}
