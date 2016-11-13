# Laravel Shopping Cart

Shopping cart for Laravel Application.


[![Build Status](https://travis-ci.org/overtrue/laravel-shopping-cart.svg?branch=master)](https://travis-ci.org/overtrue/laravel-shopping-cart)
[![Latest Stable Version](https://poser.pugx.org/overtrue/laravel-shopping-cart/v/stable.svg)](https://packagist.org/packages/overtrue/laravel-shopping-cart)
[![Latest Unstable Version](https://poser.pugx.org/overtrue/laravel-shopping-cart/v/unstable.svg)](https://packagist.org/packages/overtrue/laravel-shopping-cart)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/overtrue/laravel-shopping-cart/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/overtrue/laravel-shopping-cart/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/overtrue/laravel-shopping-cart/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/overtrue/laravel-shopping-cart/?branch=master)
[![Total Downloads](https://poser.pugx.org/overtrue/laravel-shopping-cart/downloads)](https://packagist.org/packages/overtrue/laravel-shopping-cart)
[![License](https://poser.pugx.org/overtrue/laravel-shopping-cart/license)](https://packagist.org/packages/overtrue/wechat)

# Installation

```shell
$ composer require "overtrue/laravel-shopping-cart:1.0.*"
```

  or add the following line to your project's `composer.json`:

```json
"require": {
    "overtrue/laravel-shopping-cart": "1.0.*"
}
```

then

```shell
$ composer update
```

After completion of the above, add the follow line to the section `providers` of `config/app.php`:

```php
Overtrue\LaravelShoppingCart\ServiceProvider::class,
```

And add the follow line to the section `aliases`:

```php
'Cart'      => Overtrue\LaravelShoppingCart\Facade::class,
```

# Usage

### Add item to cart

Add a new item.

```php
Item | null Cart::add(
                    string | int $id,
                    string $name,
                    int $quantity,
                    int | float $price
                    [, array $attributes = []]
                 );
```

**example:**

```php
$row = Cart::add(37, 'Item name', 5, 100.00, ['color' => 'red', 'size' => 'M']);
// Item:
//    id       => 37
//    name     => 'Item name'
//    qty      => 5
//    price    => 100.00
//    color    => 'red'
//    size     => 'M'
//    total    => 500.00
//    __raw_id => '8a48aa7c8e5202841ddaf767bb4d10da'
$rawId = $row->rawId();// get __raw_id
$row->qty; // 5
...
```

### Update item

Update the specified item.

```php
Item Cart::update(string $rawId, int $quantity);
Item Cart::update(string $rawId, array $arrtibutes);
```

**example:**

```php
Cart::update('8a48aa7c8e5202841ddaf767bb4d10da', ['name' => 'New item name');
// or only update quantity
Cart::update('8a48aa7c8e5202841ddaf767bb4d10da', 5);
```

### Get all items

Get all the items.

```php
Collection Cart::all();
```

**example:**

```php
$items = Cart::all();
```


### Get item

Get the specified item.

```php
Item Cart::get(string $rawId);
```

**example:**

```php
$item = Cart::get('8a48aa7c8e5202841ddaf767bb4d10da');
```

### Remove item

Remove the specified item by raw ID.

```php
boolean Cart::remove(string $rawId);
```

**example:**

```php
Cart::remove('8a48aa7c8e5202841ddaf767bb4d10da');
```

### Destroy cart

Clean Shopping Cart.

```php
boolean Cart::destroy();
boolean Cart::clean(); // alias of destroy();
```

**example:**

```php
Cart::destroy();// or Cart::clean();
```

### Total price

Returns the total of all items.

```php
int | float Cart::total(); // alias of totalPrice();
int | float Cart::totalPrice();
```

**example:**

```php
$total = Cart::total();
// or
$total = Cart::totalPrice();
```


### Count rows

Return the number of rows.

```php
int Cart::countRows();
```

**example:**

```php
Cart::add(37, 'Item name', 5, 100.00, ['color' => 'red', 'size' => 'M']);
Cart::add(37, 'Item name', 1, 100.00, ['color' => 'red', 'size' => 'M']);
Cart::add(37, 'Item name', 5, 100.00, ['color' => 'red', 'size' => 'M']);
Cart::add(127, 'foobar', 15, 100.00, ['color' => 'green', 'size' => 'S']);
$rows = Cart::countRows(); // 2
```


### Count quantity

Returns the quantity of all items

```php
int Cart::count($totalItems = true);
```

`$totalItems` : When `false`,will return the number of rows.

**example:**

```php
Cart::add(37, 'Item name', 5, 100.00, ['color' => 'red', 'size' => 'M']);
Cart::add(37, 'Item name', 1, 100.00, ['color' => 'red', 'size' => 'M']);
Cart::add(37, 'Item name', 5, 100.00, ['color' => 'red', 'size' => 'M']);
$count = Cart::count(); // 11 (5+1+5)
```

### Search items

Search items by property.

```php
Collection Cart::search(array $conditions);
```

**example:**

```php
$items = Cart::search(['color' => 'red']);
$items = Cart::search(['name' => 'Item name']);
$items = Cart::search(['qty' => 10]);
```

### Check empty

```php
bool Cart::isEmpty();
```

### Specifies the associated model

Specifies the associated model of item.

```php
Cart Cart::associate(string $modelName);
```

**example:**

```php
Cart::associate('App\Models\Product');
$item = Cart::get('8a48aa7c8e5202841ddaf767bb4d10da');
$item->product->name; // $item->product is instanceof 'App\Models\Product'
```


# The Collection And Item

`Collection` and `Overtrue\LaravelShoppingCart\Item` are instanceof `Illuminate\Support\Collection`, Usage Refer to：[Collections - Laravel doc.](http://laravel.com/docs/5.0/collections)

properties of `Overtrue\LaravelShoppingCart\Item`:

- `id`       - your goods item ID.
- `name`     - Name of item.
- `qty`      - Quantity of item.
- `price`    - Unit price of item.
- `total`    - Total price of item.
- `__raw_id` - Unique ID of row.
- `__model`  - Name of item associated Model.
- ... custom attributes.

And methods:

 - `rawId()` - Return the raw ID of item.

# Events

| Event Name | Parameters |
| -------  | ------- |
| `cart.adding`  | ($attributes, $cart); |
| `cart.added`  | ($attributes, $cart); |
| `cart.updating`  | ($row, $cart); |
| `cart.updated`  | ($row, $cart); |
| `cart.removing`  | ($row, $cart); |
| `cart.removed`  | ($row, $cart); |
| `cart.destroying`  | ($cart); |
| `cart.destroyed`  | ($cart); |

You can easily handle these events, for example:

```php
Event::on('cart.adding', function($attributes, $cart){
    // code
});
```

# License

MIT