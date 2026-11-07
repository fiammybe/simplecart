<?php
test('access codes are 10 characters from the unambiguous alphabet', function () {
    $code = SimplecartOrderHandler::generateAccessCode();
    assertTrue(preg_match('/^[A-HJ-NP-Z2-9]{10}$/', $code) === 1, $code);
    assertTrue($code !== SimplecartOrderHandler::generateAccessCode(), 'codes differ');
});

test('access codes are shown as XXXXX-XXXXX and accepted in any typing style', function () {
    assertSame('ABCDE-FGHJK', SimplecartOrderHandler::formatAccessCode('ABCDEFGHJK'));
    assertSame('ABCDEFGHJK', SimplecartOrderHandler::normalizeAccessCode(' abcde-fghjk '));
});

test('a new order gets an access code once and it is kept on update', function () {
    $shop = makeShop();
    $order = $shop['orders']->create();
    assertSame('', $order->getVar('access_code', 'n'));

    $code = $order->ensureAccessCode();
    assertTrue($code !== '');
    assertSame($code, $order->ensureAccessCode(), 'a second call keeps the code');

    $shop['orders']->insert($order, true);
    assertSame($code, $order->getVar('access_code', 'n'));
});

test('orders placed through placeOrder carry an access code', function () {
    $shop = makeShop();
    $result = $shop['orders']->placeOrder(
        ['name' => 'Jan', 'email' => 'jan@example.com', 'phone' => '', 'address' => '', 'helpendehanden' => ''],
        [1 => ['product_id' => 1, 'quantity' => 1]]
    );

    $order = $shop['orders']->get($result['order_id']);
    assertTrue(preg_match('/^[A-HJ-NP-Z2-9]{10}$/', $order->getVar('access_code', 'n')) === 1);
});

test('an order accepts its own code only, in constant time and in any format', function () {
    $shop = makeShop();
    $order = $shop['orders']->create();
    $code = $order->ensureAccessCode();

    assertSame(true, $order->hasValidAccessCode($code));
    assertSame(true, $order->hasValidAccessCode(strtolower(SimplecartOrderHandler::formatAccessCode($code))));
    assertSame(false, $order->hasValidAccessCode('AAAAA-AAAAA'));
    assertSame(false, $order->hasValidAccessCode(''));

    $withoutCode = $shop['orders']->create();
    assertSame(false, $withoutCode->hasValidAccessCode(''), 'an order without a code never matches');
    assertSame(false, $withoutCode->hasValidAccessCode('anything'));
});

test('an e-mail address is accepted when one of its orders has the code', function () {
    $shop = makeShop();
    $first = $shop['orders']->create();
    $second = $shop['orders']->create();
    $code = $second->ensureAccessCode();
    $first->ensureAccessCode();
    $shop['orders']->stored = [1 => $first, 2 => $second];

    assertSame(true, $shop['orders']->isValidAccessCodeForEmail('jan@example.com', $code));
    assertSame(false, $shop['orders']->isValidAccessCodeForEmail('jan@example.com', 'ZZZZZ-ZZZZZ'));
    assertSame(false, $shop['orders']->isValidAccessCodeForEmail('jan@example.com', ' - '));
    assertSame([], $shop['orders']->getOrdersByEmail('  '));
});

test('status labels come from the language files', function () {
    $shop = makeShop();
    $labels = $shop['orders']->getStatusArray();
    assertSame('Paid', $labels['paid']);
    assertSame(['pending', 'awaiting_payment', 'paid', 'reimbursed', 'closed', 'cancelled'], array_keys($labels));
});
