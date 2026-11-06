<?php
test('unknown events are refused', function () {
    assertThrows(function () { SimplecartExtension::listen('nope', function () {}); }, 'Unknown');
    assertThrows(function () { SimplecartExtension::dispatch('nope'); }, 'Unknown');
});

test('dispatch without listeners is a no-op', function () {
    $event = SimplecartExtension::dispatch(SimplecartExtension::EVENT_CART_SUMMARY, ['items' => []]);
    assertSame([], $event->getResults());
    assertSame(false, SimplecartExtension::hasListeners(SimplecartExtension::EVENT_CART_SUMMARY));
});

test('listeners run in registration order and collect results', function () {
    SimplecartExtension::listen(SimplecartExtension::EVENT_RENDER, function ($e) { $e->addResult(['text' => 'a']); });
    SimplecartExtension::listen(SimplecartExtension::EVENT_RENDER, function ($e) { $e->addResult(['text' => 'b', 'ctx' => $e->get('context')]); });
    $event = SimplecartExtension::dispatch(SimplecartExtension::EVENT_RENDER, ['context' => 'mail']);
    assertSame([['text' => 'a'], ['text' => 'b', 'ctx' => 'mail']], $event->getResults());
});

test('a failing listener of a non-blocking event is ignored', function () {
    SimplecartExtension::listen(SimplecartExtension::EVENT_CART_SUMMARY, function () { throw new RuntimeException('boom'); });
    SimplecartExtension::listen(SimplecartExtension::EVENT_CART_SUMMARY, function ($e) { $e->addResult(['name' => 'ok']); });
    $event = SimplecartExtension::dispatch(SimplecartExtension::EVENT_CART_SUMMARY);
    assertSame(1, count($event->getResults()));
});

test('a failing listener of a blocking event propagates', function () {
    SimplecartExtension::listen(SimplecartExtension::EVENT_BEFORE_PLACE, function () { throw new Exception('no seats'); });
    assertThrows(function () { SimplecartExtension::dispatch(SimplecartExtension::EVENT_BEFORE_PLACE); }, 'no seats');
});

test('boot loads the extension files once', function () {
    $file = tempnam(sys_get_temp_dir(), 'ext');
    file_put_contents($file, '<?php $GLOBALS["__loaded"] = ($GLOBALS["__loaded"] ?? 0) + 1; SimplecartExtension::listen("order.cancelled", function () {});');
    $GLOBALS['__loaded'] = 0;
    SimplecartExtension::setFileProvider(function () use ($file) { return [$file]; });
    assertTrue(SimplecartExtension::hasListeners(SimplecartExtension::EVENT_CANCELLED));
    SimplecartExtension::dispatch(SimplecartExtension::EVENT_CANCELLED);
    assertSame(1, $GLOBALS['__loaded']);
    unlink($file);
});

test('a broken extension file does not break boot', function () {
    $file = tempnam(sys_get_temp_dir(), 'ext');
    file_put_contents($file, '<?php throw new RuntimeException("bad addon");');
    SimplecartExtension::setFileProvider(function () use ($file) { return [$file]; });
    assertSame(false, SimplecartExtension::hasListeners(SimplecartExtension::EVENT_CANCELLED));
    unlink($file);
});

test('normalizeLine accepts valid lines and rejects invalid ones', function () {
    $line = SimplecartExtension::normalizeLine(['name' => ' Row A seat 1 ', 'price' => '25.5', 'quantity' => 1, 'meta' => ['seat' => 1]]);
    assertSame('Row A seat 1', $line['name']);
    assertSame(25.5, $line['price']);
    assertSame(['seat' => 1], $line['meta']);
    assertSame(null, SimplecartExtension::normalizeLine(['name' => '', 'price' => 1]));
    assertSame(null, SimplecartExtension::normalizeLine(['name' => 'x', 'price' => -1]));
    assertSame(null, SimplecartExtension::normalizeLine(['name' => 'x', 'price' => 1, 'quantity' => 0]));
    assertSame(null, SimplecartExtension::normalizeLine(['name' => 'x', 'price' => 1, 'quantity' => 1001]));
    assertSame(null, SimplecartExtension::normalizeLine(['name' => 'x', 'price' => NAN]));
});

test('API version is exposed', function () {
    assertSame(1, SimplecartExtension::getApiVersion());
    assertSame(1, SIMPLECART_EXTENSION_API);
});
