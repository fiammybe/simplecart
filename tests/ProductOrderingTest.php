<?php
/**
 * @param array<string, mixed> $fields
 */
function makeOrderedCampaign(string $name, int $weight): SimplecartCampaign
{
    $campaign = $GLOBALS['__handlers']['campaign']->create();
    $campaign->setVar('name', $name);
    $campaign->setVar('weight', $weight);
    $GLOBALS['__handlers']['campaign']->insert($campaign, true);

    return $campaign;
}

function makeOrderedProduct(string $name, int $weight, ?SimplecartCampaign $campaign = null): SimplecartProduct
{
    $handler = $GLOBALS['__handlers']['product'];
    $product = $handler->create();
    $product->setVar('name', $name);
    $product->setVar('weight', $weight);
    $product->setVar('campaign_id', $campaign ? (int)$campaign->getVar('campaign_id') : 0);
    $handler->insert($product, true);

    return $product;
}

/**
 * @return array<int, string>
 */
function shopOrder(): array
{
    $handler = $GLOBALS['__handlers']['product'];
    $campaigns = [];
    foreach ($GLOBALS['__handlers']['campaign']->getObjects() as $campaign) {
        $campaigns[(int)$campaign->getVar('campaign_id')] = $campaign;
    }

    return array_map(function (SimplecartProduct $product): string {
        return (string)$product->getVar('name');
    }, $handler->sortForShop($handler->getObjects(), $campaigns));
}

function makeEmptyShop(): void
{
    makeShop();
    $GLOBALS['__handlers']['product']->stored = [];
}

test('campaigns are ordered by weight and products inside a campaign by their own weight', function () {
    makeEmptyShop();
    $a = makeOrderedCampaign('A', 20);
    $b = makeOrderedCampaign('B', 10);
    makeOrderedProduct('a-first', 1, $a);
    makeOrderedProduct('b-second', 5, $b);
    makeOrderedProduct('b-first', 1, $b);

    assertSame(['b-first', 'b-second', 'a-first'], shopOrder());
});

test('products without a campaign come last whatever the campaign weights are', function () {
    makeEmptyShop();
    $heavy = makeOrderedCampaign('Heavy', 999);
    makeOrderedProduct('loose', -50);
    makeOrderedProduct('in-campaign', 100, $heavy);

    assertSame(['in-campaign', 'loose'], shopOrder());
});

test('the product weight does not count across campaigns', function () {
    makeEmptyShop();
    $a = makeOrderedCampaign('A', 20);
    $b = makeOrderedCampaign('B', 10);
    makeOrderedProduct('in-a', -5, $a);
    makeOrderedProduct('in-b', 1, $b);

    assertSame(['in-b', 'in-a'], shopOrder());
});

test('equal weights fall back to the name', function () {
    makeEmptyShop();
    $a = makeOrderedCampaign('Zeta', 0);
    $b = makeOrderedCampaign('Alpha', 0);
    makeOrderedProduct('y', 0, $a);
    makeOrderedProduct('x', 0, $a);
    makeOrderedProduct('z', 0, $b);
    makeOrderedProduct('b-loose', 0);
    makeOrderedProduct('a-loose', 0);

    assertSame(['z', 'x', 'y', 'a-loose', 'b-loose'], shopOrder());
});

test('a weight that is not a number is stored as zero', function () {
    makeEmptyShop();
    $product = makeOrderedProduct('p', 0);
    $product->setVar('weight', 'abc');
    $GLOBALS['__handlers']['product']->insert($product, true);

    assertSame(0, $product->getWeight());
});
