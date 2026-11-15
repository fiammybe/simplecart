<?php
function dashboardOrder(int $timestamp, string $status, float $total): SimplecartOrder
{
    $orders = $GLOBALS['__handlers']['order'];
    $order = $orders->create();
    $order->setVar('timestamp', $timestamp);
    $order->setVar('status', $status);
    $order->setVar('total_amount', $total);
    $order->setVar('customer_name', 'Jan Jansen');
    $order->setVar('customer_email', 'jan.jansen@example.com');
    $order->setVar('customer_phone', '0470 12 34 56');
    $order->setVar('customer_address', 'Kerkstraat 1, Gent');
    assertTrue($orders->insert($order, true), 'the order is stored');

    // A new order is stamped with the current time when it is stored, so the date is set afterwards
    $order->setVar('timestamp', $timestamp);
    assertTrue($orders->insert($order, true), 'the order is stored');

    return $order;
}

function dashboardItem(SimplecartOrder $order, string $name, float $price, int $quantity, int $productId = 0, int $campaignId = 0): void
{
    $items = $GLOBALS['__handlers']['orderitem'];
    $item = $items->create();
    $item->setVar('order_id', (int)$order->getVar('order_id'));
    $item->setVar('product_name', $name);
    $item->setVar('product_price', $price);
    $item->setVar('quantity', $quantity);
    $item->setVar('product_id', $productId);
    $item->setVar('campaign_id', $campaignId);
    assertTrue($items->insert($item, true), 'the item is stored');
}

function dashboardQuestion(string $label, string $format, string $options = ''): SimplecartQuestion
{
    $questions = $GLOBALS['__handlers']['question'];
    $question = $questions->create();
    $question->setVar('label', $label);
    $question->setVar('format', $format);
    $question->setVar('options', $options);
    assertTrue($questions->insert($question, true), 'the question is stored');

    return $question;
}

function dashboardAnswer(SimplecartOrder $order, SimplecartQuestion $question, string $value): void
{
    $stored = $GLOBALS['__handlers']['orderanswer']->store((int)$order->getVar('order_id'), [
        'question_id' => (int)$question->getVar('question_id'),
        'question_label' => (string)$question->getVar('label'),
        'question_format' => (string)$question->getVar('format'),
        'weight' => 0,
        'answer_value' => $value,
    ]);
    assertTrue($stored, 'the answer is stored');
}

function buildDashboard(int $offset = 0): array
{
    $h = $GLOBALS['__handlers'];
    $data = new SimplecartDashboardData($h['order'], $h['orderitem'], $h['orderanswer'], $h['question'], $h['campaign'], $h['product'], $offset);

    return $data->build(['pending' => 'Pending', 'paid' => 'Paid', 'closed' => 'Closed'], 'Other/unknown', 'english');
}

test('the dashboard of a shop without orders is empty and does not fail', function () {
    makeShop();

    $data = buildDashboard();

    assertSame([], $data['orders']);
    assertSame([], $data['questions']);
    assertSame(3, count($data['statuses']));
});

test('an order carries its date, status, total and items', function () {
    makeShop();
    $campaign = originCampaign('Spring');
    $product = originProduct('Poster', (int)$campaign->getVar('campaign_id'));
    $order = dashboardOrder(gmmktime(10, 0, 0, 11, 13, 2026), 'paid', 25.0);
    dashboardItem($order, 'Poster', 5.0, 3, (int)$product->getVar('product_id'), (int)$campaign->getVar('campaign_id'));
    dashboardItem($order, 'Ticket', 10.0, 1);

    $data = buildDashboard();
    $row = $data['orders'][0];

    assertSame('2026-11-13', $row['date']);
    assertSame('paid', $row['status']);
    assertSame(25.0, $row['total']);
    assertSame(2, count($row['items']));
    assertSame(15.0, $row['items'][0]['a']);
    assertSame((int)$campaign->getVar('campaign_id'), $row['items'][0]['c']);
    assertSame(0, $row['items'][1]['p']);
});

test('the date follows the time zone of the site', function () {
    makeShop();
    dashboardOrder(gmmktime(23, 30, 0, 11, 13, 2026), 'paid', 5.0);

    assertSame('2026-11-13', buildDashboard()['orders'][0]['date']);
    assertSame('2026-11-14', buildDashboard(3600)['orders'][0]['date']);
    assertSame('UTC+1', buildDashboard(3600)['timezone']);
    assertSame('UTC-5', buildDashboard(-18000)['timezone']);
});

test('an add-on line and a deleted product fall under unknown', function () {
    makeShop();
    $order = dashboardOrder(time(), 'pending', 40.0);
    dashboardItem($order, 'Ticket', 30.0, 1);
    dashboardItem($order, 'Gone', 10.0, 1, 99, 98);

    $data = buildDashboard();
    $products = array_column($data['products'], 'name', 'id');
    $campaigns = array_column($data['campaigns'], 'name', 'id');

    assertSame('Gone', $products[99]);
    assertSame('Other/unknown', $products[0]);
    assertSame('#98', $campaigns[98]);
    assertSame('Other/unknown', $campaigns[0]);
});

test('a product shows its current name, and a campaign only appears when an item used it', function () {
    makeShop();
    $used = originCampaign('Spring');
    originCampaign('Never ordered');
    $product = originProduct('Old name', (int)$used->getVar('campaign_id'));
    $order = dashboardOrder(time(), 'paid', 5.0);
    dashboardItem($order, 'Old name', 5.0, 1, (int)$product->getVar('product_id'), (int)$used->getVar('campaign_id'));
    $product->setVar('name', 'New name');
    $GLOBALS['__handlers']['product']->insert($product, true);

    $data = buildDashboard();

    assertSame('New name', array_column($data['products'], 'name', 'id')[(int)$product->getVar('product_id')]);
    assertSame(['Spring'], array_values(array_diff(array_column($data['campaigns'], 'name'), ['Other/unknown'])));
});

test('answers to choice questions are included, one option per value', function () {
    makeShop();
    $single = dashboardQuestion('Help?', 'radio', "Yes\nNo");
    $multiple = dashboardQuestion('Extras', 'checkbox', "Soup\nBread\nCake");
    $first = dashboardOrder(time(), 'paid', 5.0);
    $second = dashboardOrder(time(), 'paid', 5.0);
    dashboardAnswer($first, $single, 'Yes');
    dashboardAnswer($first, $multiple, "Soup\nCake");
    dashboardAnswer($second, $single, 'No');

    $data = buildDashboard();
    $questions = array_column($data['questions'], null, 'id');

    assertSame(['Yes', 'No'], $questions[(int)$single->getVar('question_id')]['options']);
    assertSame(['Soup', 'Cake'], $data['orders'][0]['answers'][(string)(int)$multiple->getVar('question_id')]);
    assertSame(['Yes'], $data['orders'][0]['answers'][(string)(int)$single->getVar('question_id')]);
    assertSame(['No'], $data['orders'][1]['answers'][(string)(int)$single->getVar('question_id')]);
});

test('the dashboard data holds no personal data and no free-text answer', function () {
    makeShop();
    $free = dashboardQuestion('Remarks', 'textarea');
    $email = dashboardQuestion('Contact', 'email');
    $order = dashboardOrder(time(), 'paid', 5.0);
    dashboardItem($order, 'Poster', 5.0, 1, 1);
    dashboardAnswer($order, $free, 'Please call me at the back door');
    dashboardAnswer($order, $email, 'someone@example.com');

    $json = SimplecartDashboardData::toJson(buildDashboard());

    foreach (['Jan Jansen', 'jan.jansen@example.com', '0470 12 34 56', 'Kerkstraat', 'back door', 'someone@example.com', 'Remarks', 'Contact'] as $private) {
        assertSame(false, strpos($json, $private), "'{$private}' is not in the data");
    }
});

test('a tag, an ampersand or a quote in a name cannot break out of the script element', function () {
    makeShop();
    $order = dashboardOrder(time(), 'paid', 5.0);
    dashboardItem($order, '</script><b onload="x">& \' Poster', 5.0, 1);

    $json = SimplecartDashboardData::toJson(buildDashboard());

    foreach (['<', '>', '&', "'", '"</script'] as $character) {
        assertSame(false, strpos(str_replace('"', '', $json), $character), "'{$character}' is escaped");
    }

    assertSame('</script><b onload="x">& \' Poster', json_decode($json, true)['orders'][0]['items'][0]['n']);
});
