<?php
function makeLegacyOrder(string $help): SimplecartOrder
{
    $orders = $GLOBALS['__handlers']['order'];
    $order = $orders->create();
    $order->setVar('customer_name', 'Jan');
    $order->setVar('helpende_hand', $help);
    $orders->insert($order, true);

    return $order;
}

function runLegacyMigration(): array
{
    $migration = new SimplecartLegacyHelpMigration(
        $GLOBALS['__handlers']['question'],
        $GLOBALS['__handlers']['orderanswer'],
        $GLOBALS['__handlers']['order']
    );

    return $migration->run();
}

test('the former fixed question becomes a required radio question for every order with its four options in both languages', function () {
    makeShop();

    $result = runLegacyMigration();
    $question = $GLOBALS['__handlers']['question']->findBySystemKey(SimplecartLegacyHelpMigration::SYSTEM_KEY);

    assertSame(1, $result['questions']);
    assertSame('radio', $question->getFormat());
    assertSame(true, $question->isRequired());
    assertSame(0, $question->getCampaignId());
    assertSame(4, count($question->getOptions()));
    assertSame('Yes, I can help', $question->getOptionsFor('english')[1]);
    assertSame('Ja, ik kan helpen bij de opbouw op vrijdag na school', $question->getOptionsFor('nederlands')[1]);
    assertSame('Kan je komen helpen?', $question->getLabelFor('nederlands'));
});

test('a stored help value becomes an answer with the label of that option', function () {
    makeShop();
    $order = makeLegacyOrder('help_2');

    $result = runLegacyMigration();
    $answers = $order->getAnswers();

    assertSame(1, $result['answers']);
    assertSame(1, count($answers));
    assertSame('I can help at a different time', $answers[0]->toRow('english')['value']);
    assertSame('Ik kan helpen tijdens de fuif', $answers[0]->toRow('nederlands')['value']);
    assertSame('Help Needed', $answers[0]->toRow('english')['label']);
});

test('orders without a stored value get no answer', function () {
    makeShop();
    makeLegacyOrder('');

    assertSame(0, runLegacyMigration()['answers']);
});

test('running the conversion again creates nothing new', function () {
    makeShop();
    makeLegacyOrder('help_1');
    makeLegacyOrder('help_4');

    runLegacyMigration();
    $second = runLegacyMigration();

    assertSame(['questions' => 0, 'answers' => 0], $second);
    assertSame(2, count($GLOBALS['__handlers']['orderanswer']->stored));
    assertSame(1, count($GLOBALS['__handlers']['question']->stored));
});

test('a value that is not one of the four is kept as it was', function () {
    makeShop();
    $order = makeLegacyOrder('something else');

    runLegacyMigration();

    assertSame('something else', $order->getAnswers()[0]->toRow('english')['value']);
});

test('on a site without multilanguage the conversion uses the plain text of the active language', function () {
    makeShop();
    makeLegacyOrder('help_3');

    withSite(['language' => 'nederlands'], ['ml_enable' => 0], function () {
        runLegacyMigration();
    });

    $question = $GLOBALS['__handlers']['question']->findBySystemKey(SimplecartLegacyHelpMigration::SYSTEM_KEY);
    assertSame("Ja, ik kan helpen bij de afbraak op vrijdag na de fuif", $question->getOptions()[2]);
});

test('the converted answer is shown once, as a normal answer', function () {
    makeShop();
    $order = makeLegacyOrder('help_1');
    runLegacyMigration();

    $text = (new OrderConfirmationEmail($order, [], [], 'EUR'))->getTextContent();

    assertSame(1, substr_count($text, 'Yes, I can help'));
});
