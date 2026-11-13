<?php
/**
 * @param array<string, mixed> $fields
 */
function makeQuestion(array $fields = []): SimplecartQuestion
{
    $handler = $GLOBALS['__handlers']['question'];
    $question = $handler->create();
    $fields += ['campaign_id' => 0, 'label' => 'Question', 'format' => 'text', 'options' => '', 'is_required' => 0, 'weight' => 0, 'active' => 1];

    foreach ($fields as $name => $value) {
        $question->setVar($name, $value);
    }

    assertTrue($handler->insert($question, true), 'the question is stored');

    return $question;
}

function answersFor(array $questions, array $posted, string $language = 'english'): SimplecartAnswerResult
{
    return (new SimplecartAnswerValidator($language))->validate($questions, $posted);
}

function field(SimplecartQuestion $question): string
{
    return SimplecartAnswerValidator::fieldName((int)$question->getVar('question_id'));
}

function answerValue(SimplecartAnswerResult $result, SimplecartQuestion $question): ?string
{
    return $result->answers[(int)$question->getVar('question_id')]['answer_value'] ?? null;
}

test('an e-mail question refuses an invalid address and accepts a valid one', function () {
    makeShop();
    $question = makeQuestion(['format' => 'email']);

    assertSame(false, answersFor([$question], [field($question) => 'not-an-address'])->isValid());
    assertSame('jan@example.com', answerValue(answersFor([$question], [field($question) => ' jan@example.com ']), $question));
});

test('a whole number question refuses text and decimals', function () {
    makeShop();
    $question = makeQuestion(['format' => 'integer']);

    assertSame(false, answersFor([$question], [field($question) => 'abc'])->isValid());
    assertSame(false, answersFor([$question], [field($question) => '1.5'])->isValid());
    assertSame('42', answerValue(answersFor([$question], [field($question) => '42']), $question));
});

test('a number question accepts a comma and stores a point', function () {
    makeShop();
    $question = makeQuestion(['format' => 'number']);

    assertSame('1.5', answerValue(answersFor([$question], [field($question) => '1,5']), $question));
    assertSame(false, answersFor([$question], [field($question) => 'x'])->isValid());
});

test('a date question refuses an impossible date and normalizes a valid one', function () {
    makeShop();
    $question = makeQuestion(['format' => 'date']);

    assertSame(false, answersFor([$question], [field($question) => '2026-02-30'])->isValid());
    assertSame(false, answersFor([$question], [field($question) => 'tomorrow'])->isValid());
    assertSame('2026-12-31', answerValue(answersFor([$question], [field($question) => '31/12/2026']), $question));
    assertSame('2026-12-31', answerValue(answersFor([$question], [field($question) => '2026-12-31']), $question));
});

test('a url question adds the scheme and refuses nonsense', function () {
    makeShop();
    $question = makeQuestion(['format' => 'url']);

    assertSame('http://example.com', answerValue(answersFor([$question], [field($question) => 'example.com']), $question));
    assertSame(false, answersFor([$question], [field($question) => 'http://'])->isValid());
});

test('a text answer that is too long is refused', function () {
    makeShop();
    $question = makeQuestion(['format' => 'text']);

    assertSame(true, answersFor([$question], [field($question) => str_repeat('a', 255)])->isValid());
    assertSame(false, answersFor([$question], [field($question) => str_repeat('a', 256)])->isValid());
});

test('a required question refuses an empty answer and spaces', function () {
    makeShop();
    $question = makeQuestion(['is_required' => 1, 'label' => 'Your name']);

    $empty = answersFor([$question], []);
    assertSame(false, $empty->isValid());
    assertTrue(strpos(array_values($empty->errors)[0], 'Your name') !== false, 'the message names the question');
    assertSame(false, answersFor([$question], [field($question) => '   '])->isValid());
});

test('a supplementary question may stay empty and then stores no answer', function () {
    makeShop();
    $question = makeQuestion(['is_required' => 0]);
    $result = answersFor([$question], [field($question) => '']);

    assertSame(true, $result->isValid());
    assertSame([], $result->answers);
});

test('a supplementary e-mail question still refuses an invalid address', function () {
    makeShop();
    $question = makeQuestion(['format' => 'email', 'is_required' => 0]);

    assertSame(false, answersFor([$question], [field($question) => 'nope'])->isValid());
});

test('a choice is posted as a position and stored as the option with its language tags', function () {
    makeShop();
    $question = makeQuestion(['format' => 'radio', 'options' => "[en]Yes[/en][nl]Ja[/nl]\n[en]No[/en][nl]Nee[/nl]"]);

    assertSame('[en]No[/en][nl]Nee[/nl]', answerValue(answersFor([$question], [field($question) => '2']), $question));
});

test('a choice outside the list is refused', function () {
    makeShop();
    $question = makeQuestion(['format' => 'select', 'options' => "A\nB"]);

    assertSame(false, answersFor([$question], [field($question) => '3'])->isValid());
    assertSame(false, answersFor([$question], [field($question) => '0'])->isValid());
    assertSame(false, answersFor([$question], [field($question) => 'A'])->isValid());
});

test('a choice question needs options', function () {
    makeShop();
    $handler = $GLOBALS['__handlers']['question'];
    $question = $handler->create();
    $question->setVar('format', 'radio');
    $question->setVar('options', '');

    assertSame(false, $handler->beforeInsert($question));
});

/**
 * Product 1 has no campaign; products 2 and 3 belong to campaign 1 and product 4 to campaign 2
 */
function makeCampaignShop(): void
{
    makeShop();
    $products = $GLOBALS['__handlers']['product'];
    $campaigns = $GLOBALS['__handlers']['campaign'];

    foreach ([1, 2] as $number) {
        $campaign = $campaigns->create();
        $campaign->setVar('name', "Campaign {$number}");
        $campaign->setVar('start_date', 86400);
        $campaigns->insert($campaign, true);
    }

    foreach ([['second', 1], ['third', 1], ['fourth', 2]] as [$name, $campaignId]) {
        $product = $products->create();
        $product->setVar('name', $name);
        $product->setVar('price', 5.0);
        $product->setVar('active', 1);
        $product->setVar('campaign_id', $campaignId);
        $products->insert($product, true);
    }

    makeQuestion(['label' => 'Late campaign', 'campaign_id' => 2, 'weight' => 1]);
    makeQuestion(['label' => 'Early campaign', 'campaign_id' => 1, 'weight' => 5]);
    makeQuestion(['label' => 'Other campaign', 'campaign_id' => 9]);
    makeQuestion(['label' => 'General second', 'weight' => 2]);
    makeQuestion(['label' => 'General first', 'weight' => 1]);
    makeQuestion(['label' => 'Switched off', 'active' => 0]);
}

function questionLabels(array $cart): array
{
    return array_map(fn ($question) => $question->getVar('label'), $GLOBALS['__handlers']['question']->getForCart($cart));
}

test('a cart without campaign products only gets the questions for every order, in order', function () {
    makeCampaignShop();

    assertSame(['General first', 'General second'], questionLabels([1 => ['product_id' => 1, 'quantity' => 1]]));
});

test('a mixed cart gets the general questions and those of each campaign in it, each once', function () {
    makeCampaignShop();
    $cart = [
        1 => ['product_id' => 1, 'quantity' => 1],
        2 => ['product_id' => 2, 'quantity' => 1],
        3 => ['product_id' => 3, 'quantity' => 2],
        4 => ['product_id' => 4, 'quantity' => 1],
    ];

    assertSame(['General first', 'General second', 'Early campaign', 'Late campaign'], questionLabels($cart));
});

test('an order with only add-on lines gets the general questions', function () {
    makeCampaignShop();

    assertSame(['General first', 'General second'], questionLabels([]));
});

test('placeOrder stores a snapshot of each answer', function () use ($customer, $cart) {
    $shop = makeShop();
    $question = makeQuestion(['label' => '[en]Your email[/en][nl]Uw e-mail[/nl]', 'format' => 'email', 'is_required' => 1, 'weight' => 3]);
    $customer['answers'] = [field($question) => 'jan@example.com'];

    $result = $shop['orders']->placeOrder($customer, $cart);
    $stored = array_values($GLOBALS['__handlers']['orderanswer']->stored);

    assertSame(1, count($stored));
    assertSame($result['order_id'], (int)$stored[0]->getVar('order_id'));
    assertSame('[en]Your email[/en][nl]Uw e-mail[/nl]', $stored[0]->getVar('question_label'));
    assertSame('email', $stored[0]->getVar('question_format'));
    assertSame(1, (int)$stored[0]->getVar('weight'), 'the position in which the question was asked');
    assertSame('jan@example.com', $stored[0]->getVar('answer_value'));
});

test('an invalid answer stops the order before anything is stored and reports the question', function () use ($customer, $cart) {
    $shop = makeShop();
    $question = makeQuestion(['format' => 'email', 'is_required' => 1]);
    $customer['answers'] = [field($question) => 'broken'];

    $error = assertThrows(function () use ($shop, $customer, $cart) { $shop['orders']->placeOrder($customer, $cart); });

    assertSame(true, $error instanceof SimplecartAnswersException);
    assertSame([(int)$question->getVar('question_id')], array_keys($error->getResult()->errors));
    assertSame([], $shop['orders']->stored);
    assertSame([], $shop['items']->stored);
});

test('a required question that is not answered stops the order', function () use ($customer, $cart) {
    $shop = makeShop();
    makeQuestion(['is_required' => 1]);

    assertThrows(function () use ($shop, $customer, $cart) { $shop['orders']->placeOrder($customer, $cart); });
    assertSame([], $shop['orders']->stored);
});

test('answers to questions that are not asked are ignored', function () use ($customer, $cart) {
    $shop = makeShop();
    makeQuestion(['campaign_id' => 9, 'is_required' => 1]);
    $customer['answers'] = ['q_999' => 'something', 'q_1' => 'not asked'];

    $shop['orders']->placeOrder($customer, $cart);

    assertSame(1, count($shop['orders']->stored));
    assertSame([], $GLOBALS['__handlers']['orderanswer']->stored);
});

test('changing or deleting a question leaves the answers on an order as they were', function () use ($customer, $cart) {
    $shop = makeShop();
    $question = makeQuestion(['label' => 'Original', 'format' => 'radio', 'options' => "One\nTwo", 'is_required' => 1]);
    $customer['answers'] = [field($question) => '2'];
    $result = $shop['orders']->placeOrder($customer, $cart);

    $question->setVar('label', 'Renamed');
    $question->setVar('options', "Uno\nDos");
    $GLOBALS['__handlers']['question']->insert($question, true);
    $GLOBALS['__handlers']['question']->delete($question);

    $order = $shop['orders']->get($result['order_id']);
    $rows = $order->getAnswerRows('english');

    assertSame([['label' => 'Original', 'value' => 'Two']], $rows);
});

test('deleting an order deletes its answers', function () use ($customer, $cart) {
    $shop = makeShop();
    $question = makeQuestion(['is_required' => 1]);
    $customer['answers'] = [field($question) => 'yes'];
    $result = $shop['orders']->placeOrder($customer, $cart);

    $order = $shop['orders']->get($result['order_id']);
    $shop['orders']->delete($order);

    assertSame([], $GLOBALS['__handlers']['orderanswer']->stored);
});

test('with transactions a failing answer rolls the whole order back', function () use ($customer, $cart) {
    $shop = makeShop();
    $question = makeQuestion(['is_required' => 1]);
    $customer['answers'] = [field($question) => 'yes'];
    $GLOBALS['__handlers']['orderanswer']->insertShouldFail = true;

    assertThrows(function () use ($shop, $customer, $cart) { $shop['orders']->placeOrder($customer, $cart); }, _MD_SIMPLECART_ANSWER_SAVE_FAIL);

    $transactions = array_values(array_filter($shop['db']->queries, function ($q) { return in_array($q, ['START TRANSACTION', 'COMMIT', 'ROLLBACK'], true); }));
    assertSame(['START TRANSACTION', 'ROLLBACK'], $transactions);
});

test('without transactions a failing answer removes the new order again', function () use ($customer, $cart) {
    $shop = makeShop();
    $shop['db']->engine = 'MyISAM';
    $question = makeQuestion(['is_required' => 1]);
    $customer['answers'] = [field($question) => 'yes'];
    $GLOBALS['__handlers']['orderanswer']->insertShouldFail = true;

    assertThrows(function () use ($shop, $customer, $cart) { $shop['orders']->placeOrder($customer, $cart); }, _MD_SIMPLECART_ANSWER_SAVE_FAIL);

    $deletes = array_values(array_filter($shop['db']->queries, function ($q) { return strpos($q, 'DELETE FROM') === 0; }));
    assertSame(3, count($deletes));
    assertSame([], array_filter($shop['db']->queries, function ($q) { return $q === 'START TRANSACTION'; }));
});

test('free text answers are escaped and kept away from the language filter', function () {
    $db = new FakeDb();
    $answer = (new SimplecartOrderanswerHandler($db))->create();
    $answer->setVar('question_label', 'Remarks');
    $answer->setVar('question_format', 'textarea');
    $answer->setVar('answer_value', "<script>alert(1)</script> [en]hi[/en][nl]dag[/nl]\nsecond line");

    $row = $answer->toHtmlRow('nederlands');

    assertSame(false, strpos($row['value'], '<script>'));
    assertSame(false, strpos($row['value'], '[en]'));
    assertTrue(strpos($row['value'], '&#91;en]hi&#91;/en]') !== false, 'tags stay visible as text');
    assertTrue(strpos($row['value'], '<br') !== false, 'line breaks are kept');
});

test('the label of a chosen option follows the language, a free answer does not', function () {
    $db = new FakeDb();
    $handler = new SimplecartOrderanswerHandler($db);

    $choice = $handler->create();
    $choice->setVar('question_label', '[en]Help[/en][nl]Hulp[/nl]');
    $choice->setVar('question_format', 'radio');
    $choice->setVar('answer_value', '[en]Yes[/en][nl]Ja[/nl]');

    $free = $handler->create();
    $free->setVar('question_label', 'Note');
    $free->setVar('question_format', 'text');
    $free->setVar('answer_value', '[en]Yes[/en][nl]Ja[/nl]');

    assertSame(['label' => 'Hulp', 'value' => 'Ja'], $choice->toRow('nederlands'));
    assertSame('[en]Yes[/en][nl]Ja[/nl]', $free->toRow('nederlands')['value']);
});

test('the checkout form rows are escaped and keep what was filled in', function () {
    makeShop();
    $question = makeQuestion(['label' => '<b>Pick</b> [en]one[/en][nl]een[/nl]', 'format' => 'radio', 'options' => "A\nB", 'is_required' => 1]);
    $id = (int)$question->getVar('question_id');

    $rows = $GLOBALS['__handlers']['question']->getFormRows([], [$id => '2'], [$id => 'Not "valid"'], 'nederlands');

    assertSame(1, count($rows));
    assertSame('&lt;b&gt;Pick&lt;/b&gt; een', $rows[0]['label']);
    assertSame('radio', $rows[0]['kind']);
    assertSame(false, $rows[0]['options'][0]['selected']);
    assertSame(true, $rows[0]['options'][1]['selected']);
    assertSame('Not &quot;valid&quot;', $rows[0]['error']);
    assertSame("q_{$id}", $rows[0]['field']);
});

test('an order without questions behaves as before', function () use ($customer, $cart) {
    $shop = makeShop();
    $shop['orders']->placeOrder($customer, $cart);

    assertSame([], $GLOBALS['__handlers']['orderanswer']->stored);
    assertSame([], $shop['db']->queries);
});

test('deleting a campaign deletes its questions but not the answers already given', function () use ($customer, $cart) {
    $shop = makeShop();
    $campaigns = $GLOBALS['__handlers']['campaign'];
    $campaign = $campaigns->create();
    $campaign->setVar('name', 'Gone');
    $campaign->setVar('start_date', 86400);
    $campaigns->insert($campaign, true);
    $campaignId = (int)$campaign->getVar('campaign_id');

    $kept = makeQuestion(['label' => 'For every order']);
    $own = makeQuestion(['label' => 'Own question', 'campaign_id' => $campaignId, 'is_required' => 1]);
    $answer = $GLOBALS['__handlers']['orderanswer'];
    $answer->store(1, ['question_id' => (int)$own->getVar('question_id'), 'question_label' => 'Own question', 'question_format' => 'text', 'weight' => 0, 'answer_value' => 'Yes']);

    $campaigns->delete($campaign);

    $remaining = array_map(fn ($question) => $question->getVar('label'), array_values($GLOBALS['__handlers']['question']->stored));
    assertSame(['For every order'], $remaining);
    assertSame(1, count($answer->stored));
});

test('answers keep the order in which the questions were asked, not the weight of each question', function () use ($customer) {
    makeCampaignShop();
    $orders = $GLOBALS['__handlers']['order'];
    $questions = $GLOBALS['__handlers']['question'];
    $cart = [1 => ['product_id' => 1, 'quantity' => 1], 2 => ['product_id' => 2, 'quantity' => 1]];

    makeQuestion(['label' => 'Guests', 'campaign_id' => 1, 'weight' => 1, 'format' => 'integer', 'is_required' => 1]);
    makeQuestion(['label' => 'Allergies', 'weight' => 50, 'is_required' => 1]);

    $asked = $questions->getForCart($cart);
    $posted = [];
    foreach ($asked as $question) {
        $posted[field($question)] = $question->getFormat() === 'integer' ? '2' : 'text';
    }

    $result = $orders->placeOrder(['answers' => $posted] + $customer, $cart);
    $labels = array_map(fn ($row) => $row['label'], $orders->get($result['order_id'])->getAnswerRows('english'));

    assertSame(array_map(fn ($question) => $question->getVar('label'), $asked), $labels);
    assertTrue(array_search('Allergies', $labels, true) < array_search('Guests', $labels, true), 'general questions are shown before those of the campaign');
});

test('a text area answer is cleaned as plain text, because the IPF text area type purifies HTML and adds comments', function () {
    assertSame(XOBJ_DTYPE_TXTBOX, SimplecartQuestionFormat::ipfType('textarea'));
    assertSame(XOBJ_DTYPE_EMAIL, SimplecartQuestionFormat::ipfType('email'));
    assertSame(XOBJ_DTYPE_URL, SimplecartQuestionFormat::ipfType('url'));

    makeShop();
    $question = makeQuestion(['format' => 'textarea']);
    $text = "<b>bold</b> & more\nsecond line";

    assertSame($text, answerValue(answersFor([$question], [field($question) => $text]), $question));
});

test('a multiple-choice question keeps the ticked options once each, in the order of the option list', function () {
    makeShop();
    $question = makeQuestion(['format' => 'checkbox', 'options' => "A
B
[en]Cee[/en][nl]Cee-nl[/nl]
D"]);

    $result = answersFor([$question], [field($question) => ['3', '1', '3']]);

    assertTrue($result->isValid(), 'ticking options is accepted');
    assertSame("A
[en]Cee[/en][nl]Cee-nl[/nl]", answerValue($result, $question));
    assertSame('3,1,3', $result->values[(int)$question->getVar('question_id')]);
});

test('a multiple-choice question refuses an option outside the list even between valid ones', function () {
    makeShop();
    $question = makeQuestion(['format' => 'checkbox', 'options' => "A
B"]);

    assertSame(false, answersFor([$question], [field($question) => ['1', '9']])->isValid());
    assertSame(false, answersFor([$question], [field($question) => ['1', 'x']])->isValid());
});

test('a multiple-choice question is required or optional like any other', function () {
    makeShop();
    $required = makeQuestion(['format' => 'checkbox', 'options' => "A
B", 'is_required' => 1]);
    $optional = makeQuestion(['format' => 'checkbox', 'options' => "A
B"]);

    assertSame(false, answersFor([$required], [])->isValid());
    assertSame(false, answersFor([$required], [field($required) => ['', ' ']])->isValid());

    $result = answersFor([$optional], [field($optional) => []]);
    assertTrue($result->isValid(), 'nothing ticked is fine for an optional question');
    assertSame(null, answerValue($result, $optional));
});

test('only a multiple-choice question accepts more than one value', function () {
    makeShop();
    $radio = makeQuestion(['format' => 'radio', 'options' => "A
B"]);
    $select = makeQuestion(['format' => 'select', 'options' => "A
B"]);

    assertSame(false, answersFor([$radio], [field($radio) => ['1', '2']])->isValid());
    assertSame(false, answersFor([$select], [field($select) => ['1', '2']])->isValid());
    assertSame('B', answerValue(answersFor([$radio], [field($radio) => ['2']]), $radio));
});

test('a multiple-choice answer shows one option per line in the language of the viewer', function () {
    $db = new FakeDb();
    $handler = new SimplecartOrderanswerHandler($db);
    $answer = $handler->create();
    $answer->setVar('question_label', 'Extras');
    $answer->setVar('question_format', 'checkbox');
    $answer->setVar('answer_value', "[en]Tea[/en][nl]Thee[/nl]
[en]Milk <b>[/en][nl]Melk <b>[/nl]");

    assertSame("Thee
Melk <b>", $answer->toRow('nederlands')['value']);
    assertSame('Thee<br />' . "
" . 'Melk &lt;b&gt;', $answer->toHtmlRow('nederlands')['value']);
});

test('the checkout form ticks again what was ticked before', function () {
    makeShop();
    $question = makeQuestion(['format' => 'checkbox', 'options' => "A
B
C"]);

    $rows = $GLOBALS['__handlers']['question']->getFormRows([1 => ['product_id' => 1, 'quantity' => 1]], [(int)$question->getVar('question_id') => '1,3']);

    assertSame('checkbox', $rows[0]['kind']);
    assertSame([true, false, true], array_map(fn ($option) => $option['selected'], $rows[0]['options']));
});
