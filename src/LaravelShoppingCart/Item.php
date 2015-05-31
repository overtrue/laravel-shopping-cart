<?php
/**
 * Item.php
 *
 * Part of Overtrue\LaravelShoppingCart.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author    overtrue <i@overtrue.me>
 * @copyright 2015 overtrue <i@overtrue.me>
 * @link      https://github.com/overtrue
 * @link      http://overtrue.me
 */

namespace Overtrue\LaravelShoppingCart;

use Illuminate\Support\Collection;

/**
 * Shopping cart item.
 */
class Item extends Collection
{

    /**
     * The Eloquent model a cart is associated with
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

        if ($property == strtolower($this->get('__model'))) {
            $model = new $this->get('__model');

            return $model->find($this->id);
        }

        return null;
    }
} //end class
