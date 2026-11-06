<?php
/**
 * @return array{db: FakeDb, orders: SimplecartOrderHandler, items: SimplecartOrderitemHandler}
 */
function makeShop(): array
{
    $db = new FakeDb();
    $orders = new SimplecartOrderHandler($db);
    $orders->objectClass = 'TestOrder';
    $items = new SimplecartOrderitemHandler($db);
    $products = new SimplecartProductHandler($db);

    $product = $products->create();
    $product->setVar('name', 'T-shirt');
    $product->setVar('price', 10.0);
    $product->setVar('active', 1);
    $products->insert($product, true);

    $GLOBALS['__handlers'] = ['order' => $orders, 'orderitem' => $items, 'product' => $products];

    return ['db' => $db, 'orders' => $orders, 'items' => $items];
}

$customer = ['name' => 'Jan', 'email' => 'jan@example.com', 'phone' => '', 'address' => '', 'helpendehanden' => ''];
$cart = [1 => ['product_id' => 1, 'quantity' => 2]];

test('without add-ons placeOrder behaves as before and uses no transaction', function () use ($customer, $cart) {
    $shop = makeShop();
    $result = $shop['orders']->placeOrder($customer, $cart);
    assertSame(20.0, $result['total']);
    assertSame(1, count($shop['items']->stored));
    assertSame([], $shop['db']->queries);
});

test('an empty order is still refused', function () use ($customer) {
    $shop = makeShop();
    assertThrows(function () use ($shop, $customer) { $shop['orders']->placeOrder($customer, []); }, _MD_SIMPLECART_EMPTY_CART);
});

test('an order with only add-on lines is accepted and committed', function () use ($customer) {
    $shop = makeShop();
    $after = null;
    SimplecartExtension::listen(SimplecartExtension::EVENT_COLLECT_LINES, function ($e) {
        $e->addResult(['name' => 'Show - row A seat 1', 'price' => 25, 'quantity' => 1, 'meta' => ['seat' => 11], 'source' => 'seats']);
        $e->addResult(['name' => 'Show - row A seat 2', 'price' => 25, 'quantity' => 1, 'meta' => ['seat' => 12], 'source' => 'seats']);
    });
    SimplecartExtension::listen(SimplecartExtension::EVENT_AFTER_PLACE, function ($e) use (&$after) { $after = $e->getPayload(); });

    $result = $shop['orders']->placeOrder($customer, []);

    assertSame(50.0, $result['total']);
    assertSame(2, count($shop['items']->stored));
    assertSame(['START TRANSACTION', 'COMMIT'], array_values(array_filter($shop['db']->queries, function ($q) { return $q === 'START TRANSACTION' || $q === 'COMMIT' || $q === 'ROLLBACK'; })));
    assertSame(2, count($after['lines']));
    assertSame(['seat' => 11], $after['lines'][0]['meta']);
    assertTrue($after['lines'][0]['orderitem_id'] > 0);
});

test('products and add-on lines can be mixed', function () use ($customer, $cart) {
    $shop = makeShop();
    SimplecartExtension::listen(SimplecartExtension::EVENT_COLLECT_LINES, function ($e) {
        $e->addResult(['name' => 'Ticket', 'price' => 30, 'quantity' => 1]);
    });
    $result = $shop['orders']->placeOrder($customer, $cart);
    assertSame(50.0, $result['total']);
    assertSame(2, count($shop['items']->stored));
});

test('beforePlace can refuse the order before anything is stored', function () use ($customer, $cart) {
    $shop = makeShop();
    SimplecartExtension::listen(SimplecartExtension::EVENT_BEFORE_PLACE, function () { throw new Exception('Seat no longer available'); });
    assertThrows(function () use ($shop, $customer, $cart) { $shop['orders']->placeOrder($customer, $cart); }, 'Seat no longer available');
    assertSame(0, count($shop['orders']->stored));
    assertSame([], $shop['db']->queries);
});

test('a failing afterPlace rolls the order back', function () use ($customer) {
    $shop = makeShop();
    SimplecartExtension::listen(SimplecartExtension::EVENT_COLLECT_LINES, function ($e) {
        $e->addResult(['name' => 'Ticket', 'price' => 30, 'quantity' => 1]);
    });
    SimplecartExtension::listen(SimplecartExtension::EVENT_AFTER_PLACE, function () { throw new Exception('hold expired'); });

    assertThrows(function () use ($shop, $customer) { $shop['orders']->placeOrder($customer, []); }, 'hold expired');

    $transactionQueries = array_values(array_filter($shop['db']->queries, function ($q) { return in_array($q, ['START TRANSACTION', 'COMMIT', 'ROLLBACK'], true); }));
    assertSame(['START TRANSACTION', 'ROLLBACK'], $transactionQueries);
});

test('an unexpected error in afterPlace is wrapped and rolled back', function () use ($customer) {
    $shop = makeShop();
    SimplecartExtension::listen(SimplecartExtension::EVENT_COLLECT_LINES, function ($e) {
        $e->addResult(['name' => 'Ticket', 'price' => 30, 'quantity' => 1]);
    });
    SimplecartExtension::listen(SimplecartExtension::EVENT_AFTER_PLACE, function () { throw new Error('type error'); });
    $e = assertThrows(function () use ($shop, $customer) { $shop['orders']->placeOrder($customer, []); }, 'Failed to place order');
    assertTrue(in_array('ROLLBACK', $shop['db']->queries, true));
});

test('an invalid add-on line aborts the order', function () use ($customer) {
    $shop = makeShop();
    SimplecartExtension::listen(SimplecartExtension::EVENT_COLLECT_LINES, function ($e) {
        $e->addResult(['name' => '', 'price' => 30]);
    });
    assertThrows(function () use ($shop, $customer) { $shop['orders']->placeOrder($customer, []); }, _MD_SIMPLECART_EXTENSION_LINE_INVALID);
    assertSame(0, count($shop['orders']->stored));
});

test('add-on lines are refused when the tables are not transactional', function () use ($customer) {
    $shop = makeShop();
    $shop['db']->engine = 'MyISAM';
    SimplecartExtension::listen(SimplecartExtension::EVENT_COLLECT_LINES, function ($e) {
        $e->addResult(['name' => 'Ticket', 'price' => 30, 'quantity' => 1]);
    });
    assertThrows(function () use ($shop, $customer) { $shop['orders']->placeOrder($customer, []); }, 'InnoDB');
    assertSame(0, count($shop['orders']->stored));
});

test('a free add-on line alone cannot make an order with total zero', function () use ($customer) {
    $shop = makeShop();
    SimplecartExtension::listen(SimplecartExtension::EVENT_COLLECT_LINES, function ($e) {
        $e->addResult(['name' => 'Invitation', 'price' => 0, 'quantity' => 1]);
    });
    assertThrows(function () use ($shop, $customer) { $shop['orders']->placeOrder($customer, []); }, 'greater than zero');
    assertTrue(in_array('ROLLBACK', $shop['db']->queries, true));
});

test('cancelling an order notifies add-ons once', function () use ($customer, $cart) {
    $shop = makeShop();
    $result = $shop['orders']->placeOrder($customer, $cart);
    $shop['db']->storedStatus[$result['order_id']] = 'pending';

    $events = [];
    SimplecartExtension::listen(SimplecartExtension::EVENT_CANCELLED, function ($e) use (&$events) { $events[] = $e->get('reason'); });

    $order = $shop['orders']->get($result['order_id']);
    $order->setVar('status', 'cancelled');
    $shop['orders']->insert($order, true);
    assertSame(['status'], $events);

    // already cancelled in the database: no second notification
    $shop['db']->storedStatus[$result['order_id']] = 'cancelled';
    $shop['orders']->insert($order, true);
    assertSame(['status'], $events);
});

test('other status changes do not notify add-ons', function () use ($customer, $cart) {
    $shop = makeShop();
    $result = $shop['orders']->placeOrder($customer, $cart);
    $shop['db']->storedStatus[$result['order_id']] = 'pending';
    $events = [];
    SimplecartExtension::listen(SimplecartExtension::EVENT_CANCELLED, function ($e) use (&$events) { $events[] = 1; });
    $order = $shop['orders']->get($result['order_id']);
    $order->setVar('status', 'paid');
    $shop['orders']->insert($order, true);
    assertSame([], $events);
});

test('deleting an order notifies add-ons', function () use ($customer, $cart) {
    $shop = makeShop();
    $result = $shop['orders']->placeOrder($customer, $cart);
    $events = [];
    SimplecartExtension::listen(SimplecartExtension::EVENT_CANCELLED, function ($e) use (&$events) { $events[] = $e->get('reason'); });
    $order = $shop['orders']->get($result['order_id']);
    $shop['orders']->delete($order);
    assertSame(['deleted'], $events);
});
