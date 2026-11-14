<?php
/** Add-on scans (scan.php?addon=...): actions with a CSRF token, and add-ons that forbid closing an order */

/** Registers a scan.resolve listener that records every payload it receives and answers with $result */
function listenToScans(array $result, array &$calls): void
{
    SimplecartExtension::listen(SimplecartExtension::EVENT_SCAN_RESOLVE, function ($event) use ($result, &$calls) {
        $calls[] = $event->getPayload();
        $event->addResult($result);
    });
}

function adminScan(array $extra = []): array
{
    return $extra + ['params' => ['addon' => 'seats', 'ticket' => '42'], 'method' => 'GET', 'is_admin' => true, 'user' => 'admin'];
}

test('API_REVISION announces scan actions', function () {
    assertTrue(SimplecartExtension::API_REVISION >= 3);
    assertSame(1, SimplecartExtension::API_VERSION, 'the change is additive');
});

test('a GET scan reaches the add-on with method GET and no action', function () {
    $calls = [];
    listenToScans(['title' => 'Ticket 42', 'message' => 'Row C seat 12'], $calls);

    $scan = SimplecartExtension::resolveScan(adminScan(['action' => 'accept']));

    assertSame(null, $scan['error']);
    assertSame(1, count($calls));
    assertSame('GET', $calls[0]['method']);
    assertSame(false, array_key_exists('action', $calls[0]), 'no action on a GET, even when one is sent');
    assertSame('seats', $calls[0]['addon']);
    assertSame('42', $calls[0]['params']['ticket']);
});

test('a valid POST reaches the add-on with method POST and the action', function () {
    $calls = [];
    listenToScans(['message' => 'Ticket taken in', 'status' => 'success'], $calls);

    $scan = SimplecartExtension::resolveScan(adminScan(['method' => 'POST', 'action' => 'accept', 'token_valid' => true]));

    assertSame(null, $scan['error']);
    assertSame('POST', $calls[0]['method']);
    assertSame('accept', $calls[0]['action']);
    assertSame('Ticket taken in', $scan['scan']['message']);
    assertSame('success', $scan['scan']['status']);
});

test('a POST with an invalid token does not reach the add-on', function () {
    $calls = [];
    listenToScans(['message' => 'x'], $calls);

    $scan = SimplecartExtension::resolveScan(adminScan(['method' => 'POST', 'action' => 'accept', 'token_valid' => false]));
    assertSame('token_invalid', $scan['error']);

    $scan = SimplecartExtension::resolveScan(adminScan(['method' => 'POST', 'action' => 'accept']));
    assertSame('token_invalid', $scan['error'], 'a missing token check counts as invalid');

    assertSame(0, count($calls));
});

test('a scan without admin rights does not reach the add-on', function () {
    $calls = [];
    listenToScans(['message' => 'x'], $calls);

    assertSame('no_access', SimplecartExtension::resolveScan(adminScan(['is_admin' => false]))['error']);
    assertSame('no_access', SimplecartExtension::resolveScan(adminScan(['is_admin' => false, 'method' => 'POST', 'action' => 'accept', 'token_valid' => true]))['error']);
    assertSame(0, count($calls));
});

test('a POST with an invalid action name does not reach the add-on', function () {
    $calls = [];
    listenToScans(['message' => 'x'], $calls);

    foreach ([null, '', 'Accept', 'accept-now', 'accept1', str_repeat('a', 33), ['accept']] as $action) {
        $scan = SimplecartExtension::resolveScan(adminScan(['method' => 'POST', 'action' => $action, 'token_valid' => true]));
        assertSame('action_invalid', $scan['error'], var_export($action, true));
    }
    assertSame(0, count($calls));
});

test('actions are validated: name, label, confirm and style', function () {
    $actions = SimplecartExtension::normalizeScanActions([
        ['name' => 'accept', 'label' => 'Take in ticket'],
        ['name' => 'force_accept', 'label' => 'Take in anyway', 'confirm' => 'Not paid. Continue?', 'style' => 'warning'],
        ['name' => 'Bad-Name', 'label' => 'x'],
        ['name' => str_repeat('a', 33), 'label' => 'too long'],
        ['name' => str_repeat('a', 32), 'label' => 'just fits', 'style' => 'danger'],
        ['name' => 'no_label'],
        ['name' => 'array_label', 'label' => ['x']],
        ['name' => 'accept', 'label' => 'duplicate'],
        'not an array',
    ]);

    assertSame([
        ['name' => 'accept', 'label' => 'Take in ticket', 'confirm' => '', 'style' => 'primary'],
        ['name' => 'force_accept', 'label' => 'Take in anyway', 'confirm' => 'Not paid. Continue?', 'style' => 'warning'],
        ['name' => str_repeat('a', 32), 'label' => 'just fits', 'confirm' => '', 'style' => 'primary'],
    ], $actions);

    assertSame([], SimplecartExtension::normalizeScanActions('accept'));
});

test('the scan page escapes the label and confirm text of actions', function () {
    $template = file_get_contents(ICMS_ROOT_PATH . '/templates/simplecart_order_scan.html.tpl');

    foreach (['$action.label', '$action.confirm', '$action.name', '$simplecart_scan_extension.token', '$simplecart_scan_extension.form_action'] as $variable) {
        preg_match_all('/<\{' . preg_quote($variable, '/') . '([^}]*)\}>/', $template, $matches);
        assertTrue(count($matches[1]) > 0, "{$variable} is used");
        foreach ($matches[1] as $modifiers) {
            assertSame('|escape', $modifiers, "{$variable} is escaped");
        }
    }
});

test('an add-on without actions works as before', function () {
    $calls = [];
    listenToScans(['title' => 'Ticket 42', 'message' => 'Valid', 'status' => 'odd', 'html' => '<p>seat</p>'], $calls);

    $scan = SimplecartExtension::resolveScan(adminScan());

    assertSame([
        'title' => 'Ticket 42',
        'message' => 'Valid',
        'status' => 'warning',
        'html' => '<p>seat</p>',
        'actions' => [],
    ], $scan['scan']);
});

test('a scan nobody answers is an invalid link', function () {
    assertSame('invalid', SimplecartExtension::resolveScan(adminScan())['error']);
});

test('a failing add-on is an invalid link, not an error page', function () {
    SimplecartExtension::listen(SimplecartExtension::EVENT_SCAN_RESOLVE, function () { throw new RuntimeException('boom'); });

    ob_start();
    $scan = SimplecartExtension::resolveScan(adminScan());
    ob_end_clean();

    assertSame('invalid', $scan['error']);
});

test('the token name of an add-on only holds safe characters', function () {
    assertSame('simplecart_scan_seats', SimplecartExtension::scanTokenName('seats'));
    assertSame('simplecart_scan_my_addonx', SimplecartExtension::scanTokenName('My_Addon"><x'));
});

test('allow_close = false forbids closing the order', function () {
    assertSame(true, SimplecartExtension::scanAllowsClose([]));
    assertSame(true, SimplecartExtension::scanAllowsClose([['html' => '<p>x</p>'], ['allow_close' => true], ['allow_close' => null]]));
    assertSame(false, SimplecartExtension::scanAllowsClose([['html' => '<p>x</p>'], ['allow_close' => false]]));
    assertSame(false, SimplecartExtension::scanAllowsClose([['allow_close' => 0]]));
});

test('the scan page checks allow_close when showing and when closing', function () {
    $script = file_get_contents(ICMS_ROOT_PATH . '/scan.php');

    $check = strpos($script, '$allowsClose = SimplecartExtension::scanAllowsClose(');
    $refuse = strpos($script, "if (!\$allowsClose) {\n        redirect_header(\$scanUrl, 3, _MD_SIMPLECART_SCAN_CLOSE_NOT_ALLOWED);");
    $close = strpos($script, '->closeScannedOrder(');
    $show = strpos($script, "if (\$status === 'paid' && \$allowsClose) {");

    assertTrue($check !== false && $refuse !== false && $close !== false && $show !== false, 'all checks are present');
    assertTrue($check < $refuse && $refuse < $close, 'the POST refuses before it closes');
});
