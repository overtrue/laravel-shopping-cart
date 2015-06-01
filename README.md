# Laravel Shopping Cart

Shopping cart for Laravel Application.

# Installation

```shell
composer require "overtrue/laravel-shopping-cart:dev-master"
```

  or add the following line to your project's `composer.json`:

```json
"require": {
    "overtrue/laravel-lang": "dev-master"
}
```

then

```shell
composer update
```

After completion of the above, add the follow line to the section `providers` of `config/app.php`:

```php
'Overtrue\LaravelShoppingCart\ServiceProvider',
```

And add the follow line to the section `aliases`:

```php
'Cart'      => 'Overtrue\LaravelShoppingCart\Facade',
```

## Usage

### Add item to cart

Add a new item.

**syntax:**

```php
string | null Cart::add(
                    string | int $id,
                    string $name,
                    int $quantity,
                    int | float $price
                    [, array $attributes = []]
                 );
```

**example:**

```php
$rawId = Cart::add(37, 'Item name', 5, 100.00, ['color' => 'red', 'size' => 'M']);
// Item:
//    id       => 37
//    name     => 'Item name'
//    qty      => 5
//    price    => 100.00
//    color    => 'red'
//    size     => 'M'
//    total    => 500.00
//    __raw_id => '8a48aa7c8e5202841ddaf767bb4d10da'
```

### Update item

Update the specified item.

**syntax:**

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

**syntax:**

```php
Collection Cart::all();
```

**example:**

```php
$items = Cart::all();
```


### Get item

Get the specified item.

**syntax:**

```php
Item Cart::get(string $rawId);
```

**example:**

```php
$item = Cart::get('8a48aa7c8e5202841ddaf767bb4d10da');
```

### Remove item

Remove the specified item by raw ID.

**syntax:**

```php
boolean Cart::remove(string $rawId);
```

**example:**

```php
Cart::remove('8a48aa7c8e5202841ddaf767bb4d10da');
```

### Destroy cart

Empty Shopping Cart.

**syntax:**

```php
boolean Cart::destroy(string $rawId);
```

**example:**

```php
Cart::destroy();
```

### Total price

Returns the total of all items.

**syntax:**

```php
int | float Cart::total();
```

**example:**

```php
$total = Cart::total();
```


### Count rows

Return the number of rows.

**syntax:**

```php
int Cart::countRows();
```

**example:**

```php
$rows = Cart::countRows();
```


### Count quantity

Returns the number of all items

**syntax:**

```php
int Cart::count($totalItems = true);
```

`$totalItems` : When `false`,will return the number of rows.

**example:**

```php
$count = Cart::count();
$rows = Cart::count(false); // same as Cart::countRows();
```

### Search items

Search items by property.

**syntax:**

```php
Collection Cart::search(array $conditions);
```

**example:**

```php
$items = Cart::search(['color' => 'red']);
$items = Cart::search(['name' => 'Item name']);
$items = Cart::search(['qty' => 10]);
```

## Specifies the associated model

Specifies the associated model of item.

**syntax:**

```php
Cart Cart::associate(string $modelName);
```

**example:**

```php
Cart::associate('App\Models\Product');
$item = Cart::get('8a48aa7c8e5202841ddaf767bb4d10da');
$item->product->name; // $item->product is instanceof 'App\Models\Product'
```



## the Collection And Item

`Collection` and `Overtrue\LaravelShoppingCart\Item` both are instanceof `Illuminate\Support\Collection`, Usage Refer to：[Collections - Laravel doc.](http://laravel.com/docs/5.0/collections)

properties of `Overtrue\LaravelShoppingCart\Item`:

- `id`       - your goods item ID.
- `name`     - Name of item.
- `qty`      - Quantity of item.
- `price`    - Unit price of item.
- `total`    - Total price of item.
- `__raw_id` - Unique ID of row.
- `__model`  - Name of item associated Model.

And methods:

 - `rawId()` - Return the raw ID of item.

## License

MIT