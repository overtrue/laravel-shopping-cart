<?php

use Mockery as m;
use Overtrue\LaravelShoppingCart\Cart;
use Illuminate\Support\Collection;

class CartTest extends PHPUnit_Framework_TestCase
{
    protected $cart;

    public function setUp()
    {
        $sessionManager = new Illuminate\Session\SessionManager();
        $this->cart = new Cart($sessionManager, $this->event());
    }

    public function event()
    {
        $event = m::mock('Illuminate\Contracts\Events\Dispatcher');
        $event->shouldReceive('fire')->andReturn(null);

        return $event;
    }

    public function tearDown()
    {
        m::close();
    }

    /**
     * test name().
     */
    public function testName()
    {
        $this->assertEquals('shopping_cart.default', $this->cart->getName());

        $this->cart->name('overtrue');

        $this->assertEquals('shopping_cart.overtrue', $this->cart->getName());
    }

    /**
     * test add().
     */
    public function testAdd()
    {
        $row = $this->cart->add(1, 'foo', 5, 100.00);

        $this->assertEquals('Overtrue\LaravelShoppingCart\Item', get_class($row));
        $this->assertEquals('foo', $row->name);
        $this->assertEquals(5, $row->qty);
        $this->assertEquals(100.00, $row->price);
        $this->assertEquals(500.00, $row->total);

        // string ID
        $row = $this->cart->add('stringid', 'string item', 1, 10.00);
        $this->assertEquals('stringid', $row->id);

        // add a exists item
        $row = $this->cart->add(1, 'foo', 5, 100.00);

        $this->assertEquals('foo', $row->name);
        $this->assertEquals(10, $row->qty);
        $this->assertEquals(100.00, $row->price);
        $this->assertEquals(1000.00, $row->total);

        // https://github.com/overtrue/laravel-shopping-cart/issues/2
        $this->cart->clean();
        $this->cart->add(37, 'Item name', 5, 100.00, ['color' => 'red', 'size' => 'M']);
        $this->cart->add(37, 'Item name', 1, 100.00, ['color' => 'red', 'size' => 'M']);
        $this->cart->add(37, 'Item name', 5, 100.00, ['color' => 'red', 'size' => 'M']);

        $this->assertEquals(11, $this->cart->count());
        $this->assertEquals(1, $this->cart->countRows());
        $this->assertEquals(1100.00, $this->cart->total());
    }

    /**
     * test add() with bad quantity.
     *
     * @expectedException Overtrue\LaravelShoppingCart\Exception
     */
    public function testAddBadQty()
    {
        $row = $this->cart->add(1, 'foo', 0, 100.00);
    }

    /**
     * test add() with bad price.
     *
     * @expectedException Overtrue\LaravelShoppingCart\Exception
     */
    public function testAddBadPrice()
    {
        $row = $this->cart->add(1, 'foo', 10, -100.00);
    }

    /**
     * test update().
     */
    public function testUpdate()
    {
        $row = $this->cart->add(1, 'foo', 5, 100.00);

        $rawId = $row->rawId();

        // single property
        $return = $this->cart->update($rawId, ['qty' => 100]);

        $updated = $this->cart->get($rawId);

        $this->assertEquals($return, $updated);
        $this->assertEquals(100, $updated->qty);
        $this->assertEquals(10000.00, $updated->total);

        // multi properties
        $updated = $this->cart->update($rawId, ['name' => 'bar', 'price' => 20.00]);
        $fetched = $this->cart->get($rawId);

        $this->assertEquals($fetched, $updated);
        $this->assertEquals('bar', $updated->name);
        $this->assertEquals(100, $updated->qty);
        $this->assertEquals(20.00, $updated->price);
        $this->assertEquals(2000.00, $updated->total);

        // int arguments
        $updated = $this->cart->update($rawId, 30);
        $fetched = $this->cart->get($rawId);

        $this->assertEquals($fetched, $updated);

        $this->assertEquals('bar', $updated->name);
        $this->assertEquals(30, $updated->qty);

        // update with quantity (0)
        $this->cart->update($rawId, 0);

        $this->assertNull($this->cart->get($rawId));
    }

    /**
     * test update() with non-exists raw id.
     *
     * @expectedException Overtrue\LaravelShoppingCart\Exception
     */
    public function testUpdateBadRawId()
    {
        $this->cart->update('foo', 5);
    }

    /**
     * test remove().
     */
    public function testRemove()
    {
        $row1 = $this->cart->add(14, 'foobar', 5, 10.00);
        $row2 = $this->cart->add(13, 'foobarbaz', 20, 5.00);

        $row1Id = $row1->rawId();
        $row2Id = $row2->rawId();

        // non-exists
        $this->assertTrue($this->cart->remove('foobar'));

        $this->assertEquals($row1, $this->cart->get($row1Id));

        $this->cart->remove($row1Id);

        $this->assertNull($this->cart->get($row1Id));
        $this->assertEquals($row2, $this->cart->all()->pop());
    }

    /**
     * test get().
     */
    public function testGet()
    {
        $this->assertNull($this->cart->get('badId'));

        $row1 = $this->cart->add(14, 'foo', 5, 10.00);

        $this->assertEquals($row1, $this->cart->get($row1->rawId()));

        $row2 = $this->cart->add(15, 'bar', 1, 40.00);

        $this->assertEquals($row1, $this->cart->get($row1->rawId()));
        $this->assertEquals($row2, $this->cart->get($row2->rawId()));
    }

    /**
     * test all();.
     */
    public function testAllAndCountAndCountRows()
    {
        $this->cart->destroy();
        $row1 = $this->cart->add(14, 'foo', 5, 10.00);
        $row2 = $this->cart->add(16, 'bar', 10, 1.00);

        $this->assertTrue($this->cart->all()->has($row1->rawId()));
        $this->assertTrue($this->cart->all()->has($row2->rawId()));
        $this->assertEquals(15, $this->cart->count());
        $this->assertEquals(2, $this->cart->count(false));
        $this->assertEquals(2, $this->cart->countRows(false));

        $this->cart->update($row1->rawId(), 1);

        $this->assertEquals(11, $this->cart->count());

        $this->cart->remove($row1->rawId(), 0);
        $this->assertEquals(1, $this->cart->countRows());

        $this->assertFalse($this->cart->isEmpty());

        $this->cart->destroy();

        $this->assertTrue($this->cart->isEmpty());
        $this->assertEquals(0, $this->cart->all()->count());
    }

    /**
     * test total().
     */
    public function testTotal()
    {
        $this->cart->destroy();

        $this->assertEquals(0, $this->cart->total());

        $row1 = $this->cart->add(14, 'foo', 5, 10.00);
        $row2 = $this->cart->add(15, 'foo', 3, 5.00);
        $row3 = $this->cart->add(16, 'bar', 10, 1.00);
        $row4 = $this->cart->add(17, 'bar', 10, 1.00);

        $this->assertEquals(85.0, $this->cart->total());
    }

    /**
     * test search().
     */
    public function testSearch()
    {
        $this->cart->destroy();

        $this->assertEquals(new Collection(), $this->cart->search([]));

        $row1 = $this->cart->add(14, 'foo', 5, 10.00);
        $row2 = $this->cart->add(15, 'foo', 3, 5.00);
        $row3 = $this->cart->add(16, 'bar', 10, 1.00);
        $row4 = $this->cart->add(17, 'bar', 10, 1.00);

        $this->assertContains($row1, $this->cart->search(['name' => 'foo']));
        $this->assertContains($row2, $this->cart->search(['name' => 'foo']));
        $this->assertEquals(2, $this->cart->search(['price' => 1])->count());
        $this->assertEquals($row2, $this->cart->search(['qty' => 3])->first());
        $this->assertEquals($row2, $this->cart->search(['id' => 15])->first());
        $this->assertEquals($row4, $this->cart->search(['id' => 17])->first());
        $this->assertTrue($this->cart->search(['name' => 'baz'])->isEmpty());
    }

    /**
     * test associate().
     */
    public function testAssociate()
    {
        $this->cart->destroy();

        $row1 = $this->cart->add(14, 'foo', 5, 10.00);

        $this->assertNull($row1->foobar);// non-exists

        $this->cart->associate('App\Models\Product');

        $row1 = $this->cart->add(15, 'foo', 5, 10.00);

        $this->assertEquals('App\Models\Product', $this->cart->getModel());

        $this->assertEquals($row1->product->id, $row1->id);
        $this->assertNull($row1->foooooooo);
    }

    /**
     * test associate() with non-exists model name.
     *
     * @expectedException Overtrue\LaravelShoppingCart\Exception
     */
    public function testBadModelName()
    {
        $this->cart->associate('App\Foo\Bar');
    }
}
