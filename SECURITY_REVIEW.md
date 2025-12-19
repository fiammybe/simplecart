# Security Verification – SimpleCart (ImpressCMS 2.0.1)

Context: PHP8+ host, module directory `src/`, public AJAX endpoints in `ajax.php`, admin pages under `src/admin/`. Review performed against commit `2e0500777182cf0c14dff335928df5090d47440a`.

## Findings

1) **Perpetual CSRF tokens and token leakage via GET links (Medium)**  
Snippet: `src/ajax.php` line 31 and `src/class/order.php` line 26 generate CSRF tokens with unlimited lifetime and embed them in GET URLs; `src/admin/order.php` lines 53-75 later validate them:  
```php
$token = icms::$security->createToken(0, 'simplecart');
...
$url = $base . '?op=changestatus&order_id=' . $id . '&status=' . $key . '&token=' . urlencode($token);
...
if (!icms::$security->check(true, $token, 'simplecart_order_status')) {
    redirect_header('order.php', 3, 'Security token invalid.');
}
```
Tokens never expire (`0` TTL) and are sent in GET URLs, so they can be replayed indefinitely and leaked via referrer headers, logs, or browser history, enabling CSRF replay if a token is captured.  
Severity: **Medium**.  
OWASP: *CWE-352 Cross-Site Request Forgery; OWASP ASVS V3.5; OWASP Top 10 2021 A01 (Broken Access Control) / A05 (Security Misconfiguration).*  
Fix: (1) Issue short-lived, single-use tokens tied to each action (e.g., `createToken(3600, 'simplecart_order_status')` for admin status changes, `createToken(3600, 'simplecart')` for order placement). (2) Send them in POST bodies and invalidate on first use (e.g., `check(true, $token, 'simplecart_order_status')`). (3) Convert the status change action to POST with CSRF-protected forms.

2) **Order placement accepts invalid carts and unbounded quantities (Business Logic / DoS) (Medium)**  
Snippet: `src/ajax.php` lines 69-92 process items but never verify that at least one *valid* item was persisted after filtering invalid products; quantities are unbounded:  
```php
foreach ($items as $it) {
    $pid = isset($it['product_id']) ? (int)$it['product_id'] : 0;
    $qty = isset($it['quantity']) ? (int)$it['quantity'] : 0;
    if ($pid <= 0 || $qty <= 0) { continue; }
    ...
    if (!$prod || $prod->isNew() || (int)$prod->getVar('active') !== 1) { continue; }
    ...
    $orderItemHandler->insert($item, true);
    $total += $qty * $price;
}

$order->setVar('total_amount', $total);
$orderHandler->insert($order, true);
```
Empty carts are blocked earlier in `src/ajax.php` line 50. An attacker can send a payload with only invalid product IDs (or extremely large quantities), creating orders with `total_amount` 0 and no items, bloating the database and order queue. Lack of upper bounds allows very large quantities to inflate totals or stress storage.  
Severity: **Medium**.  
OWASP: *A04 Insecure Design / Business Logic Abuse; CWE-840 Business Logic Errors.*  
Fix: Validate that at least one order item was successfully added; reject or roll back the order when none are valid. Enforce reasonable quantity bounds (e.g., 1–1000), and reject requests whose computed total is zero or exceeds configured limits. Add rate limiting/throttling on `place_order`.

3) **External JS/CSS loaded without integrity or pinning (Supply Chain) (Medium)**  
Snippet: `src/templates/simplecart_index.html` line 1 (Bulma) & line 61 (Vue) and `src/templates/simplecart_checkout.html` line 1 (Bulma) & line 56 (Vue) fetch assets from CDNs without SRI or pinning:  
```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
```
Unpinned third-party assets can be replaced upstream, enabling malicious script injection or defacement if the CDN or network is compromised.  
Severity: **Medium**.  
OWASP: *A08 Software and Data Integrity Failures; CWE-494 Download of Code Without Integrity Check.*  
Fix: Vendor these assets locally, or include SRI hashes with fixed versions (`integrity` + `crossorigin="anonymous"`). Consider CSP to restrict script sources.

## Final Summary
- **Overall risk:** Medium. No immediate RCE/SQLi found, but CSRF hardening, business-logic validation, and supply-chain controls are needed.
- **Refactoring priorities:**  
  1. Short-lived POST-based CSRF tokens for admin state changes and order API.  
  2. Server-side validation of cart contents (at least one valid item, bounded quantities, non-zero totals) plus rate limiting.  
  3. Pin or self-host front-end dependencies with integrity metadata and add CSP.
- **Missing safeguards:** Rate limiting on `place_order`, item/quantity bounds, expiring CSRF tokens, CSP + SRI for external assets, and logging/alerting for suspicious order creation attempts.
