<?php
function makeDatedCampaign(string $name, int $start, int $end = 0): SimplecartCampaign
{
    $handler = $GLOBALS['__handlers']['campaign'];
    $campaign = $handler->create();
    $campaign->setVar('name', $name);
    $campaign->setVar('start_date', $start);
    $campaign->setVar('end_date', $end);
    assertTrue($handler->insert($campaign, true), 'the campaign is stored');

    return $campaign;
}

function headlineName(): string
{
    $campaign = $GLOBALS['__handlers']['campaign']->getHeadlineCampaign();

    return $campaign ? (string)$campaign->getVar('name') : '';
}

test('the headline is the active campaign with the earliest start date', function () {
    makeShop();
    makeDatedCampaign('Later', time() - 100000);
    makeDatedCampaign('Earlier', time() - 200000);

    assertSame('Earlier', headlineName());
});

test('without an active campaign there is no headline', function () {
    makeShop();
    makeDatedCampaign('Future', time() + 100000);
    makeDatedCampaign('Ended', time() - 200000, time() - 100000);

    assertSame('', headlineName());
});

test('campaigns that started together are ordered by name', function () {
    makeShop();
    $start = time() - 100000;
    makeDatedCampaign('Beta', $start);
    makeDatedCampaign('alpha', $start);

    assertSame('alpha', headlineName());
});

test('an inactive campaign with an earlier start is skipped', function () {
    makeShop();
    makeDatedCampaign('Ended', time() - 300000, time() - 100000);
    makeDatedCampaign('Running', time() - 200000);

    assertSame('Running', headlineName());
});

test('the confirmation subject carries the headline campaign in the language of the customer', function () {
    $order = makeDutchOrder();
    makeDatedCampaign('Vredestraat Fuif', time() - 100000);
    $email = new OrderConfirmationEmail($order, $order->getItems(), [], 'EUR');

    assertSame('Bestellingsbevestiging Vredestraat Fuif #' . $order->getVar('order_id'), $email->getSubject());
});

test('the confirmation subject has no campaign name when no campaign is active', function () {
    $order = makeDutchOrder();
    makeDatedCampaign('Future', time() + 100000);
    $email = new OrderConfirmationEmail($order, $order->getItems(), [], 'EUR');

    assertSame('Bestellingsbevestiging #' . $order->getVar('order_id'), $email->getSubject());
});
