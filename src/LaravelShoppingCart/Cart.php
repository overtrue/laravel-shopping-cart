<?php
/**
 * Cart.php
 *
 * Part of Overtrue\LaravelShoppingCart.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Adapted from https://github.com/Crinsane/LaravelShoppingcart
 *
 * @author    overtrue <i@overtrue.me>
 * @copyright 2015 overtrue <i@overtrue.me>
 * @link      https://github.com/overtrue
 * @link      http://overtrue.me
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
     * Session manager
     *
     * @var \Illuminate\Session\SessionManager
     */
    protected $session;

    /**
     * Event dispatcher
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $event;

    /**
     * Current cart name
     *
     * @var string
     */
    protected $name = 'cart.default';

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
        $this->event   = $event;
    }

    /**
     * Set the current cart name
     *
     * @param string $name Cart name name
     *
     * @return Cart
     */
    public function name($name)
    {
        $this->name = 'cart.' . $name;

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
        $this->model = $model;

        if (!class_exists($model)) {
            throw new Exception("Invalid model name '$model'.");
        }

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
     * Add a row to the cart
     *
     * @param int | string $id         Unique ID of the item
     * @param string       $name       Name of the item
     * @param int          $qty        Item qty to add to the cart
     * @param float        $price      Price of one item
     * @param array        $attributes Array of additional attributes, such as 'size' or 'color'...
     *
     * @return string
     */
    public function add($id, $name = null, $qty = null, $price = null, array $attributes = [])
    {
        $attributes = array_merge(compact('id', 'name', 'qty', 'price'), $attributes);

        $cart = $this->getCart();

        $this->event->fire('cart.add', [$attributes, $cart]);

        $row = $this->addRow($id, $name, $qty, $price, $attributes);

        $this->event->fire('cart.added', [$attributes, $cart]);

        return $row;
    }

    /**
     * Update the quantity of one row of the cart
     *
     * @param string    $rowId     The __raw_id of the item you want to update
     * @param int|array $attribute New quantity of the item|Array of attributes to update
     *
     * @return Item
     */
    public function update($rowId, $attribute)
    {
        if (!$row = $this->get($rowId)) {
            throw new Exception('Item not found.');
        }

        $cart = $this->getCart();

        $this->event->fire('cart.update', [$row, $cart]);

        if (is_array($attribute)) {
            $raw = $this->updateAttribute($rowId, $attribute);
        } else {
            $raw = $this->updateQty($rowId, $attribute);
        }

        $this->event->fire('cart.updated', [$row, $cart]);

        return $raw;
    }

    /**
     * Remove a row from the cart
     *
     * @param string $rowId The __raw_id of the item
     *
     * @return boolean
     */
    public function remove($rowId)
    {
        if (!$row = $this->get($rowId)) {
            return true;
        }

        $cart = $this->getCart();

        $this->event->fire('cart.remove', [$row, $cart]);

        $cart->forget($rowId);

        $this->event->fire('cart.removed', [$row, $cart]);

        return $this->syncCart($cart);
    }

    /**
     * Get a row of the cart by its ID
     *
     * @param string $rowId The ID of the row to fetch
     *
     * @return Item
     */
    public function get($rowId)
    {
        $row = $this->getCart()->get($rowId);

        return is_null($row) ? null : new Item($row);
    }

    /**
     * Clean the cart
     *
     * @return boolean
     */
    public function destroy()
    {
        $this->event->fire('cart.destroy', $this->name);

        $this->syncCart(null);

        $this->event->fire('cart.destroyed', $this->name);

        return true;
    }

    /**
     * Get the price total
     *
     * @return float
     */
    public function total()
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
     * Get the number of items in the cart
     *
     * @param boolean $totalItems Get all the items (when false, will return the number of rows)
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
     * Get rows count
     *
     * @return int
     */
    public function countRows()
    {
        return $this->count(false);
    }

    /**
     * Search if the cart has a item
     *
     * @param array $search An array with the item ID and optional options
     *
     * @return array
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
     * Get current associated model
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Add row to the cart
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

        $rowId = $this->generateRawId($id, $attributes);

        if ($row = $cart->get($rowId)) {
            $row = $this->updateQty($rowId, $row->qty + $qty);
        } else {
            $row = $this->insertRow($rowId, $id, $name, $qty, $price, $attributes);
        }

        return $row;
    }

    /**
     * Generate a unique id for the new row
     *
     * @param string $id         Unique ID of the item
     * @param array  $attributes Array of additional options, such as 'size' or 'color'
     *
     * @return string
     */
    protected function generateRawId($id, $attributes)
    {
        ksort($attributes);

        return md5($id . serialize($attributes));
    }

    /**
     * Sync the cart to session.
     *
     * @param array $cart The new cart content
     *
     * @return \Illuminate\Support\Collection
     */
    protected function syncCart($cart)
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
     * Update a row if the rowId already exists.
     *
     * @param string    $rowId      The ID of the row to update
     * @param int|array $attributes The quantity to add to the row
     *
     * @return Item
     */
    protected function updateRow($rowId, $attributes)
    {
        $cart = $this->getCart();

        $row = $cart->get($rowId);

        foreach ($attributes as $key => $value) {
            $row->put($key, $value);
        }

        if (!empty(array_intersect(array_keys($attributes), ['qty', 'price']))) {
            $row->put('total', $row->qty * $row->price);
        }

        $cart->put($rowId, $row);

        return $row;
    }

    /**
     * Create a new row Object.
     *
     * @param string $rowId      The ID of the new row
     * @param string $id         Unique ID of the item
     * @param string $name       Name of the item
     * @param int    $qty        Item qty to add to the cart
     * @param float  $price      Price of one item
     * @param array  $attributes Array of additional options, such as 'size' or 'color'
     *
     * @return Item
     */
    protected function insertRow($rowId, $id, $name, $qty, $price, $attributes = [])
    {
        $newRow = $this->makeRow($rowId, $id, $name, $qty, $price, $attributes);

        $cart = $this->getCart();

        $cart->put($rowId, $newRow);

        $this->syncCart($cart);

        return $newRow;
    }

    /**
     * Make a row item.
     *
     * @param string $rowId      Raw id.
     * @param mixed  $id         Item id.
     * @param string $name       Item name.
     * @param int    $qty        Quantity.
     * @param float  $price      Price.
     * @param array  $attributes Other attributes.
     *
     * @return Item
     */
    protected function makeRow($rowId, $id, $name, $qty, $price, array $attributes = [])
    {
        return new Item(array_merge([
                                     '__raw_id' => $rowId,
                                     'id'       => $id,
                                     'name'     => $name,
                                     'qty'      => $qty,
                                     'price'    => $price,
                                     'total'    => $qty * $price,
                                     '__model'  => $this->model,
                                    ], $attributes));
    }

    /**
     * Update the quantity of a row
     *
     * @param string $rowId The ID of the row
     * @param int    $qty   The qty to add
     *
     * @return Item | void | boolean
     */
    protected function updateQty($rowId, $qty)
    {
        if ($qty <= 0) {
            return $this->remove($rowId);
        }

        return $this->updateRow($rowId, ['qty' => $qty]);
    }

    /**
     * Update an attribute of the row
     *
     * @param string $rowId      The ID of the row
     * @param array  $attributes An array of attributes to update
     *
     * @return Item
     */
    protected function updateAttribute($rowId, $attributes)
    {
        return $this->updateRow($rowId, $attributes);
    }
}//end class
