<?php

/*
 * This file is part of the overtrue/laravel-shopping-cart.
 *
 * (c) 2016 overtrue <i@overtrue.me>
 */

namespace Overtrue\LaravelShoppingCart;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Collection;

/**
 * Main class of Overtrue\LaravelShoppingCart package.
 */
class Cart
{
    /**
     * Session manager.
     *
     * @var \Illuminate\Session\SessionManager
     */
    protected $session;

    /**
     * Event dispatcher.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $event;

    /**
     * Current cart name.
     *
     * @var string
     */
    protected $name = 'shopping_cart.default';

    /**
     * Associated model name.
     *
     * @var string
     */
    protected $model;

    /**
     * Constructor.
     *
     * @param \Illuminate\Session\SessionManager      $session Session class name
     * @param \Illuminate\Contracts\Events\Dispatcher $event   Event class name
     */
    public function __construct(SessionManager $session, Dispatcher $event)
    {
        $this->session = $session;
        $this->event = $event;
    }

    public function dispatchEvent($event, $payload = [], $halt = false)
    {
        if (method_exists($this->event, 'fire')) {
            return $this->event->fire($event, $payload, $halt);
        }

        return $this->event->dispatch($event, $payload, $halt);
    }

    /**
     * Set the current cart name.
     *
     * @param string $name Cart name name
     *
     * @return Cart
     */
    public function name($name)
    {
        $this->name = 'shopping_cart.'.$name;

        return $this;
    }

    /**
     * Associated model.
     *
     * @param string $model The name of the model
     *
     * @return Cart
     */
    public function associate($model)
    {
        if (!class_exists($model)) {
            throw new Exception("Invalid model name '$model'.");
        }
        $this->model = $model;

        return $this;
    }

    /**
     * Get all items.
     *
     * @return \Illuminate\Support\Collection
     */
    public function all()
    {
        return $this->getCart();
    }

    /**
     * Add a row to the cart.
     *
     * @param int|string $id         Unique ID of the item
     * @param string     $name       Name of the item
     * @param int        $qty        Item qty to add to the cart
     * @param float      $price      Price of one item
     * @param array      $attributes Array of additional attributes, such as 'size' or 'color'...
     *
     * @return string
     */
    public function add($id, $name = null, $qty = null, $price = null, array $attributes = [])
    {
        $cart = $this->getCart();

        $this->dispatchEvent('shopping_cart.adding', [$attributes, $cart]);

        $row = $this->addRow($id, $name, $qty, $price, $attributes);

        $cart = $this->getCart();

        $this->dispatchEvent('shopping_cart.added', [$attributes, $cart]);

        return $row;
    }

    /**
     * Update the quantity of one row of the cart.
     *
     * @param string    $rawId     The __raw_id of the item you want to update
     * @param int|array $attribute New quantity of the item|Array of attributes to update
     *
     * @return Item|bool
     */
    public function update($rawId, $attribute)
    {
        if (!$row = $this->get($rawId)) {
            throw new Exception('Item not found.');
        }

        $cart = $this->getCart();

        $this->dispatchEvent('shopping_cart.updating', [$row, $cart]);

        if (is_array($attribute)) {
            $raw = $this->updateAttribute($rawId, $attribute);
        } else {
            $raw = $this->updateQty($rawId, $attribute);
        }

        $this->dispatchEvent('shopping_cart.updated', [$row, $cart]);

        return $raw;
    }

    /**
     * Remove a row from the cart.
     *
     * @param string $rawId The __raw_id of the item
     *
     * @return bool
     */
    public function remove($rawId)
    {
        if (!$row = $this->get($rawId)) {
            return true;
        }

        $cart = $this->getCart();

        $this->dispatchEvent('shopping_cart.removing', [$row, $cart]);

        $cart->forget($rawId);

        $this->dispatchEvent('shopping_cart.removed', [$row, $cart]);

        $this->save($cart);

        return true;
    }

    /**
     * Get a row of the cart by its ID.
     *
     * @param string $rawId The ID of the row to fetch
     *
     * @return Item
     */
    public function get($rawId)
    {
        $row = $this->getCart()->get($rawId);

        return null === $row ? null : new Item($row);
    }

    /**
     * Clean the cart.
     *
     * @return bool
     */
    public function destroy()
    {
        $cart = $this->getCart();

        $this->dispatchEvent('shopping_cart.destroying', $cart);

        $this->save(null);

        $this->dispatchEvent('shopping_cart.destroyed', $cart);

        return true;
    }

    /**
     * Alias of destory().
     *
     * @return bool
     */
    public function clean()
    {
        $this->destroy();
    }

    /**
     * Get the price total.
     *
     * @return float
     */
    public function total()
    {
        return $this->totalPrice();
    }

    /**
     * Return total price of cart.
     *
     * @return
     */
    public function totalPrice()
    {
        $total = 0;

        $cart = $this->getCart();

        if ($cart->isEmpty()) {
            return $total;
        }

        foreach ($cart as $row) {
            $total += $row->qty * $row->price;
        }

        return $total;
    }

    /**
     * Get the number of items in the cart.
     *
     * @param bool $totalItems Get all the items (when false, will return the number of rows)
     *
     * @return int
     */
    public function count($totalItems = true)
    {
        $items = $this->getCart();

        if (!$totalItems) {
            return $items->count();
        }

        $count = 0;

        foreach ($items as $row) {
            $count += $row->qty;
        }

        return $count;
    }

    /**
     * Get rows count.
     *
     * @return int
     */
    public function countRows()
    {
        return $this->count(false);
    }

    /**
     * Search if the cart has a item.
     *
     * @param array $search An array with the item ID and optional options
     *
     * @return array|Collection
     */
    public function search(array $search)
    {
        $rows = new Collection();

        if (empty($search)) {
            return $rows;
        }

        foreach ($this->getCart() as $item) {
            if (array_intersect_assoc($item->intersect($search)->toArray(), $search)) {
                $rows->put($item->__raw_id, $item);
            }
        }

        return $rows;
    }

    /**
     * Get current cart name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get current associated model.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Return whether the shopping cart is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->count() <= 0;
    }

    /**
     * Add row to the cart.
     *
     * @param string $id         Unique ID of the item
     * @param string $name       Name of the item
     * @param int    $qty        Item qty to add to the cart
     * @param float  $price      Price of one item
     * @param array  $attributes Array of additional options, such as 'size' or 'color'
     *
     * @return string
     */
    protected function addRow($id, $name, $qty, $price, array $attributes = [])
    {
        if (!is_numeric($qty) || $qty < 1) {
            throw new Exception('Invalid quantity.');
        }

        if (!is_numeric($price) || $price < 0) {
            throw new Exception('Invalid price.');
        }

        $cart = $this->getCart();

        $rawId = $this->generateRawId($id, $attributes);

        if ($row = $cart->get($rawId)) {
            $row = $this->updateQty($rawId, $row->qty + $qty);
        } else {
            $row = $this->insertRow($rawId, $id, $name, $qty, $price, $attributes);
        }

        return $row;
    }

    /**
     * Generate a unique id for the new row.
     *
     * @param string $id         Unique ID of the item
     * @param array  $attributes Array of additional options, such as 'size' or 'color'
     *
     * @return string
     */
    protected function generateRawId($id, $attributes)
    {
        ksort($attributes);

        return md5($id.serialize($attributes));
    }

    /**
     * Sync the cart to session.
     *
     * @param \Illuminate\Support\Collection|null $cart The new cart content
     *
     * @return \Illuminate\Support\Collection
     */
    protected function save($cart)
    {
        $this->session->put($this->name, $cart);

        return $cart;
    }

    /**
     * Get the carts content.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getCart()
    {
        $cart = $this->session->get($this->name);

        return $cart instanceof Collection ? $cart : new Collection();
    }

    /**
     * Update a row if the rawId already exists.
     *
     * @param string $rawId      The ID of the row to update
     * @param array  $attributes The quantity to add to the row
     *
     * @return Item
     */
    protected function updateRow($rawId, array $attributes)
    {
        $cart = $this->getCart();

        $row = $cart->get($rawId);

        foreach ($attributes as $key => $value) {
            $row->put($key, $value);
        }

        if (count(array_intersect(array_keys($attributes), ['qty', 'price']))) {
            $row->put('total', $row->qty * $row->price);
        }

        $cart->put($rawId, $row);

        return $row;
    }

    /**
     * Create a new row Object.
     *
     * @param string $rawId      The ID of the new row
     * @param string $id         Unique ID of the item
     * @param string $name       Name of the item
     * @param int    $qty        Item qty to add to the cart
     * @param float  $price      Price of one item
     * @param array  $attributes Array of additional options, such as 'size' or 'color'
     *
     * @return Item
     */
    protected function insertRow($rawId, $id, $name, $qty, $price, $attributes = [])
    {
        $newRow = $this->makeRow($rawId, $id, $name, $qty, $price, $attributes);

        $cart = $this->getCart();

        $cart->put($rawId, $newRow);

        $this->save($cart);

        return $newRow;
    }

    /**
     * Make a row item.
     *
     * @param string $rawId      raw id
     * @param mixed  $id         item id
     * @param string $name       item name
     * @param int    $qty        quantity
     * @param float  $price      price
     * @param array  $attributes other attributes
     *
     * @return Item
     */
    protected function makeRow($rawId, $id, $name, $qty, $price, array $attributes = [])
    {
        return new Item(array_merge([
            '__raw_id' => $rawId,
            'id' => $id,
            'name' => $name,
            'qty' => $qty,
            'price' => $price,
            'total' => $qty * $price,
            '__model' => $this->model,
        ], $attributes));
    }

    /**
     * Update the quantity of a row.
     *
     * @param string $rawId The ID of the row
     * @param int    $qty   The qty to add
     *
     * @return Item|bool
     */
    protected function updateQty($rawId, $qty)
    {
        if ($qty <= 0) {
            return $this->remove($rawId);
        }

        return $this->updateRow($rawId, ['qty' => $qty]);
    }

    /**
     * Update an attribute of the row.
     *
     * @param string $rawId      The ID of the row
     * @param array  $attributes An array of attributes to update
     *
     * @return Item
     */
    protected function updateAttribute($rawId, $attributes)
    {
        return $this->updateRow($rawId, $attributes);
    }
}
