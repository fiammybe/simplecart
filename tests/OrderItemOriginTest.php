<?php
function originProduct(string $name, int $campaignId = 0): SimplecartProduct
{
    $products = $GLOBALS['__handlers']['product'];
    $product = $products->create();
    $product->setVar('name', $name);
    $product->setVar('price', 5.0);
    $product->setVar('active', 1);
    $product->setVar('campaign_id', $campaignId);
    assertTrue($products->insert($product, true), 'the product is stored');

    return $product;
}

function originCampaign(string $name): SimplecartCampaign
{
    $campaigns = $GLOBALS['__handlers']['campaign'];
    $campaign = $campaigns->create();
    $campaign->setVar('name', $name);
    $campaign->setVar('start_date', time() - 100000);
    $campaign->setVar('end_date', 0);
    assertTrue($campaigns->insert($campaign, true), 'the campaign is stored');

    return $campaign;
}

/** An item as it was stored before 2.8.0: a name and no origin */
function originLegacyItem(string $name): SimplecartOrderitem
{
    $items = $GLOBALS['__handlers']['orderitem'];
    $item = $items->create();
    $item->setVar('order_id', 1);
    $item->setVar('product_name', $name);
    $item->setVar('product_price', 5.0);
    $item->setVar('quantity', 1);
    assertTrue($items->insert($item, true), 'the item is stored');

    return $item;
}

function runOriginMigration(): array
{
    $migration = new SimplecartOrderitemOriginMigration($GLOBALS['__handlers']['orderitem'], $GLOBALS['__handlers']['product']);

    return $migration->run();
}

test('an ordered product keeps its id and the campaign it was in', function () use ($customer) {
    $shop = makeShop();
    $campaign = originCampaign('Spring');
    $product = originProduct('Poster', (int)$campaign->getVar('campaign_id'));

    $shop['orders']->placeOrder($customer, [['product_id' => (int)$product->getVar('product_id'), 'quantity' => 1]]);

    $item = array_values($shop['items']->stored)[0];
    assertSame((int)$product->getVar('product_id'), (int)$item->getVar('product_id'));
    assertSame((int)$campaign->getVar('campaign_id'), (int)$item->getVar('campaign_id'));
});

test('an ordered product without a campaign keeps no campaign', function () use ($customer, $cart) {
    $shop = makeShop();

    $shop['orders']->placeOrder($customer, $cart);

    $item = array_values($shop['items']->stored)[0];
    assertSame(1, (int)$item->getVar('product_id'));
    assertSame(0, (int)$item->getVar('campaign_id'));
});

test('an add-on line has no product and no campaign', function () use ($customer) {
    $shop = makeShop();
    SimplecartExtension::listen(SimplecartExtension::EVENT_COLLECT_LINES, function ($e) {
        $e->addResult(['name' => 'Ticket', 'price' => 30, 'quantity' => 1]);
    });

    $shop['orders']->placeOrder($customer, []);

    $item = array_values($shop['items']->stored)[0];
    assertSame(0, (int)$item->getVar('product_id'));
    assertSame(0, (int)$item->getVar('campaign_id'));
});

test('the origin of an item stays when the product moves or is renamed later', function () use ($customer) {
    $shop = makeShop();
    $campaign = originCampaign('Spring');
    $product = originProduct('Poster', (int)$campaign->getVar('campaign_id'));
    $shop['orders']->placeOrder($customer, [['product_id' => (int)$product->getVar('product_id'), 'quantity' => 1]]);

    $product->setVar('campaign_id', 0);
    $product->setVar('name', 'Big poster');
    $GLOBALS['__handlers']['product']->insert($product, true);

    $item = array_values($shop['items']->stored)[0];
    assertSame((int)$campaign->getVar('campaign_id'), (int)$item->getVar('campaign_id'));
    assertSame('Poster', (string)$item->getVar('product_name'));
});

test('the migration links an item to the one product with its name', function () {
    makeShop();
    $campaign = originCampaign('Spring');
    $product = originProduct('Poster', (int)$campaign->getVar('campaign_id'));
    $item = originLegacyItem('Poster');

    $result = runOriginMigration();

    assertSame(1, $result['linked']);
    assertSame((int)$product->getVar('product_id'), (int)$item->getVar('product_id'));
    assertSame((int)$campaign->getVar('campaign_id'), (int)$item->getVar('campaign_id'));
});

test('the migration leaves an item whose name no product has', function () {
    makeShop();
    $item = originLegacyItem('Removed product');

    $result = runOriginMigration();

    assertSame(0, $result['linked']);
    assertSame(1, $result['skipped']);
    assertSame(0, (int)$item->getVar('product_id'));
});

test('the migration leaves an item whose name more than one product has', function () {
    makeShop();
    originProduct('Poster');
    originProduct('Poster');
    $item = originLegacyItem('Poster');

    $result = runOriginMigration();

    assertSame(0, $result['linked']);
    assertSame(0, (int)$item->getVar('product_id'));
});

test('running the migration again changes nothing', function () {
    makeShop();
    $campaign = originCampaign('Spring');
    $product = originProduct('Poster', (int)$campaign->getVar('campaign_id'));
    $item = originLegacyItem('Poster');
    originLegacyItem('Removed product');
    runOriginMigration();

    // The product moves after the first run; an item that was linked is not touched again
    $product->setVar('campaign_id', 0);
    $GLOBALS['__handlers']['product']->insert($product, true);
    $result = runOriginMigration();

    assertSame(0, $result['linked']);
    assertSame(1, $result['skipped']);
    assertSame((int)$campaign->getVar('campaign_id'), (int)$item->getVar('campaign_id'));
});
