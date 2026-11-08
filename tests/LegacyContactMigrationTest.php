<?php
function makeContactOrder(string $phone, string $address): SimplecartOrder
{
    $orders = $GLOBALS['__handlers']['order'];
    $order = $orders->create();
    $order->setVar('customer_name', 'Jan');
    $order->setVar('customer_phone', $phone);
    $order->setVar('customer_address', $address);
    $orders->insert($order, true);

    return $order;
}

function runContactMigration(): array
{
    $migration = new SimplecartLegacyContactMigration(
        $GLOBALS['__handlers']['question'],
        $GLOBALS['__handlers']['orderanswer'],
        $GLOBALS['__handlers']['order']
    );

    return $migration->run();
}

test('phone and address become optional questions for every order, in both languages', function () {
    makeShop();

    $result = runContactMigration();
    $questions = $GLOBALS['__handlers']['question'];
    $phone = $questions->findBySystemKey(SimplecartLegacyContactMigration::SYSTEM_KEY_PHONE);
    $address = $questions->findBySystemKey(SimplecartLegacyContactMigration::SYSTEM_KEY_ADDRESS);

    assertSame(2, $result['questions']);
    assertSame('text', $phone->getFormat());
    assertSame('textarea', $address->getFormat());
    assertSame(false, $phone->isRequired());
    assertSame(false, $address->isRequired());
    assertSame(0, $phone->getCampaignId());
    assertSame('Telefoon', $phone->getLabelFor('nederlands'));
    assertSame('Address', $address->getLabelFor('english'));
    assertTrue($phone->getWeight() < $address->getWeight());
});

test('stored phone and address become answers in that order', function () {
    makeShop();
    $order = makeContactOrder('0470 12 34 56', "Straat 1\n9000 Gent");

    $result = runContactMigration();
    $answers = $order->getAnswers();

    assertSame(2, $result['answers']);
    assertSame(2, count($answers));
    assertSame('0470 12 34 56', $answers[0]->toRow('english')['value']);
    assertSame('Phone Number', $answers[0]->toRow('english')['label']);
    assertSame("Straat 1\n9000 Gent", $answers[1]->toRow('nederlands')['value']);
});

test('an order with only a phone number gets one answer', function () {
    makeShop();
    $order = makeContactOrder('0470 12 34 56', '');

    assertSame(1, runContactMigration()['answers']);
    assertSame(1, count($order->getAnswers()));
});

test('orders without phone or address get no answers', function () {
    makeShop();
    makeContactOrder('', '  ');

    assertSame(0, runContactMigration()['answers']);
});

test('running the contact conversion again creates nothing new', function () {
    makeShop();
    makeContactOrder('111', 'Straat 1');
    makeContactOrder('222', 'Straat 2');

    runContactMigration();
    $second = runContactMigration();

    assertSame(['questions' => 0, 'answers' => 0], $second);
    assertSame(4, count($GLOBALS['__handlers']['orderanswer']->stored));
    assertSame(2, count($GLOBALS['__handlers']['question']->stored));
});

test('the converted phone shows once in the confirmation e-mail, as a normal answer', function () {
    makeShop();
    $order = makeContactOrder('0470 12 34 56', '');
    runContactMigration();

    $text = (new OrderConfirmationEmail($order, [], [], 'EUR'))->getTextContent();

    assertSame(1, substr_count($text, '0470 12 34 56'));
});
