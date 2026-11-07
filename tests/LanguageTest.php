<?php
/**
 * Runs a test body with other site settings and puts the old ones back
 *
 * @param array<string, string> $site
 * @param array<string, mixed> $multilanguage
 */
function withSite(array $site, array $multilanguage, callable $body)
{
    $old = [$GLOBALS['icmsConfig'], $GLOBALS['icmsConfigMultilang']];
    $GLOBALS['icmsConfig'] = $site + $old[0];
    $GLOBALS['icmsConfigMultilang'] = $multilanguage + $old[1];
    SimplecartEmailText::reset();

    try {
        return $body();
    } finally {
        [$GLOBALS['icmsConfig'], $GLOBALS['icmsConfigMultilang']] = $old;
        SimplecartEmailText::reset();
    }
}

test('a text is resolved to the block of the language', function () {
    $text = '[en]Yes[/en][nl]Ja[/nl]';

    assertSame('Ja', SimplecartLanguage::resolve($text, 'nederlands'));
    assertSame('Yes', SimplecartLanguage::resolve($text, 'english'));
});

test('a language without a block gets the first block', function () {
    assertSame('Yes', SimplecartLanguage::resolve('[en]Yes[/en][nl]Ja[/nl]', 'german'));
    assertSame('Ja', SimplecartLanguage::resolve('[nl]Ja[/nl][fr]Oui[/fr]', 'english'));
});

test('a text without tags and text around the blocks are kept', function () {
    assertSame('Plain text', SimplecartLanguage::resolve('Plain text', 'nederlands'));
    assertSame('Dag [wereld] Ja!', SimplecartLanguage::resolve('Dag [wereld] [en]Yes[/en][nl]Ja[/nl]!', 'nederlands'));
});

test('tags are resolved when multilanguage is switched off or not configured at all', function () {
    withSite([], ['ml_enable' => 0], function () {
        assertSame('Ja', SimplecartLanguage::resolve('[en]Yes[/en][nl]Ja[/nl]', 'nederlands'));
    });
    withSite([], ['ml_enable' => 0, 'ml_tags' => '', 'ml_names' => ''], function () {
        assertSame('Ja', SimplecartLanguage::resolve('[en]Yes[/en][nl]Ja[/nl]', 'nederlands'));
    });
});

test('the tag of a language comes from the system settings by position', function () {
    assertSame('nl', SimplecartLanguage::tagFor('nederlands'));

    withSite([], ['ml_tags' => 'fr,en', 'ml_names' => 'french,english'], function () {
        assertSame('fr', SimplecartLanguage::tagFor('french'));
        assertSame('en', SimplecartLanguage::tagFor('english'));
        assertSame('Oui', SimplecartLanguage::resolve('[en]Yes[/en][fr]Oui[/fr]', 'french'));
    });

    assertSame('', SimplecartLanguage::tagFor('klingon'));
});

test('a language the module has no texts for falls back to the site default', function () {
    assertSame('nederlands', SimplecartLanguage::normalize('nederlands'));
    assertSame('english', SimplecartLanguage::normalize('klingon'));
    assertSame('english', SimplecartLanguage::normalize(''));

    withSite(['language' => 'nederlands'], [], function () {
        assertSame('nederlands', SimplecartLanguage::normalize(''));
    });
});

test('a tagged text gets a block per language, or plain text when multilanguage is off', function () {
    $texts = ['english' => 'Yes', 'nederlands' => 'Ja'];
    $textFor = fn (string $language): string => $texts[$language] ?? '?';

    assertSame('[en]Yes[/en][nl]Ja[/nl]', SimplecartLanguage::tagged($textFor));

    withSite(['language' => 'nederlands'], ['ml_enable' => 0], function () use ($textFor) {
        assertSame('Ja', SimplecartLanguage::tagged($textFor));
    });
});

test('every e-mail text exists in every language', function () {
    $english = require SIMPLECART_ROOT_PATH . 'language/english/email.php';
    $dutch = require SIMPLECART_ROOT_PATH . 'language/nederlands/email.php';

    assertSame([], array_values(array_diff(array_keys($english), array_keys($dutch))));
    assertSame([], array_values(array_diff(array_keys($dutch), array_keys($english))));
});

test('the e-mail texts follow the language, with fallbacks that never fail', function () {
    assertSame('Dear', SimplecartEmailText::forLanguage('english')->get('EMAIL_GREETING'));
    assertSame('Beste', SimplecartEmailText::forLanguage('nederlands')->get('EMAIL_GREETING'));
    assertSame('Dear', SimplecartEmailText::forLanguage('klingon')->get('EMAIL_GREETING'));
    assertSame('NO_SUCH_KEY', SimplecartEmailText::forLanguage('nederlands')->get('NO_SUCH_KEY'));
});

test('an order keeps the language of the customer, or the site default for a language without texts', function () use ($customer, $cart) {
    $shop = makeShop();

    $dutch = $shop['orders']->placeOrder(['language' => 'nederlands'] + $customer, $cart);
    $unknown = $shop['orders']->placeOrder(['language' => 'klingon'] + $customer, $cart);

    assertSame('nederlands', $shop['orders']->get($dutch['order_id'])->getVar('customer_language'));
    assertSame('english', $shop['orders']->get($unknown['order_id'])->getVar('customer_language'));
});

test('an order from before the language was recorded follows the active language', function () use ($customer, $cart) {
    $shop = makeShop();
    $result = $shop['orders']->placeOrder($customer, $cart);
    $order = $shop['orders']->get($result['order_id']);
    $order->setVar('customer_language', '');

    assertSame('english', $order->getLanguage());
    withSite(['language' => 'nederlands'], [], function () use ($order) {
        assertSame('nederlands', $order->getLanguage());
    });
});

/**
 * An order placed in Dutch with one answer, while the site and the person sending the mail work in English
 */
function makeDutchOrder(): SimplecartOrder
{
    global $customer, $cart;

    $shop = makeShop();
    $question = makeQuestion(['label' => '[en]Allergies[/en][nl]Allergieën[/nl]', 'format' => 'radio', 'options' => "[en]None[/en][nl]Geen[/nl]\n[en]Nuts[/en][nl]Noten[/nl]", 'is_required' => 1]);
    $result = $shop['orders']->placeOrder(['language' => 'nederlands', 'answers' => [field($question) => '2']] + $customer, $cart);

    return $shop['orders']->get($result['order_id']);
}

test('the confirmation e-mail is in the language of the customer', function () {
    $order = makeDutchOrder();
    $text = (new OrderConfirmationEmail($order, $order->getItems(), [], 'EUR'))->getTextContent();

    assertTrue(strpos($text, 'Beste') !== false, 'greeting in Dutch');
    assertSame(false, strpos($text, 'Dear'));
    assertTrue(strpos($text, 'Uw antwoorden') !== false, 'heading of the answers');
    assertTrue(strpos($text, 'Allergieën: Noten') !== false, 'label and chosen option in Dutch');
    assertSame(false, strpos($text, '[nl]'));
});

test('the payment received e-mail is in the language of the customer when an administrator in another language sends it', function () {
    $order = makeDutchOrder();
    $email = new PaymentReceivedEmail($order, $order->getItems(), [], 'EUR');

    assertSame('Betaling ontvangen - Bestelling #' . $order->getVar('order_id'), $email->getSubject());

    $text = $email->getTextContent();
    assertTrue(strpos($text, 'Betaling ontvangen!') !== false, 'heading in Dutch');
    assertTrue(strpos($text, 'Allergieën: Noten') !== false, 'answer in Dutch');

    $html = $email->getHtmlContent('qr');
    assertTrue(strpos($html, 'Uw antwoorden') !== false, 'heading of the answers in the html');
    assertTrue(strpos($html, '<strong>Allergieën</strong>') !== false, 'label in the html');
    assertSame(false, strpos($html, 'Payment Received'));
});

test('the same order is written in English for an English customer', function () use ($customer, $cart) {
    $shop = makeShop();
    $question = makeQuestion(['label' => '[en]Allergies[/en][nl]Allergieën[/nl]', 'format' => 'text']);
    $result = $shop['orders']->placeOrder(['language' => 'english', 'answers' => [field($question) => 'Hay fever']] + $customer, $cart);
    $order = $shop['orders']->get($result['order_id']);

    $text = (new OrderConfirmationEmail($order, $order->getItems(), [], 'EUR'))->getTextContent();

    assertTrue(strpos($text, 'Dear') !== false, 'greeting in English');
    assertTrue(strpos($text, 'Allergies: Hay fever') !== false);
});

test('add-ons are told the language when they build mail text', function () {
    $order = makeDutchOrder();
    $seen = null;
    SimplecartExtension::listen(SimplecartExtension::EVENT_RENDER, function ($event) use (&$seen) { $seen = $event->getPayload(); });

    (new OrderConfirmationEmail($order, $order->getItems(), [], 'EUR'))->getTextContent();

    assertSame('mail', $seen['context']);
    assertSame('nederlands', $seen['language']);
});

test('the order date in an e-mail uses the format of the language, not that of the request', function () {
    $order = makeDutchOrder();
    $order->setVar('timestamp', mktime(14, 5, 0, 3, 9, 2026));

    $text = (new OrderConfirmationEmail($order, $order->getItems(), [], 'EUR'))->getTextContent();

    assertTrue(strpos($text, '09-03-2026 14:05') !== false);
});
