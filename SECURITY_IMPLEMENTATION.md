# Security Implementation Guide for SimpleCart Module

## Overview
This document details the security measures implemented in the SimpleCart ImpressCMS module and provides guidance for deployment and maintenance.

## Security Measures Implemented

### 1. CSRF Protection (OWASP A01:2021 - Broken Access Control)

**Issue:** Original implementation used perpetual CSRF tokens (TTL=0) sent via GET parameters.

**Fix Implemented:**
- **Time-Limited Tokens:** All CSRF tokens now expire after 1 hour (3600 seconds)
  - `ajax.php` line 80: Order placement tokens
  - `class/order.php` lines 28, 48: Admin action tokens
  
- **POST-Based Actions:** Admin state-changing operations now use POST with hidden form fields
  - Status changes: `class/order.php` getStatusActionLinks()
  - Delete operations: `class/order.php` getDeleteLink()
  - Request validation: `admin/order.php` line 54-56

**Implementation:**
```php
// Before (INSECURE):
$token = icms::$security->createToken(0, 'simplecart');

// After (SECURE):
$token = icms::$security->createToken(3600, 'simplecart_order_status');
```

### 2. Business Logic Validation (OWASP A04:2021 - Insecure Design)

**Issues:** 
- No validation for empty orders
- Unbounded quantities
- Zero-value orders accepted

**Fix Implemented:**
- **Minimum Valid Items:** Orders must contain at least one valid product (`ajax.php` lines 213-216)
- **Quantity Bounds:** Quantities limited to 1-1000 per item (`ajax.php` lines 184, 187-189)
- **Zero-Total Prevention:** Orders with $0 total are rejected (`ajax.php` lines 218-221)
- **Client-Side Enforcement:** Defense-in-depth quantity limit in JavaScript (`cart.js` lines 16-20)

**Implementation:**
```php
$maxQuantityPerItem = 1000;
if ($qty > $maxQuantityPerItem) {
    throw new Exception("Quantity exceeds maximum allowed");
}
if ($validItemCount === 0 || $total <= 0) {
    throw new Exception('Invalid order');
}
```

### 3. Rate Limiting (OWASP A04:2021 - Insecure Design)

**Issue:** No protection against order spam or DoS attacks.

**Fix Implemented:**
- **Order Placement Rate Limit:** 5 orders per 5 minutes per IP address
- Function: `simplecart_checkRateLimit()` in `ajax.php` lines 9-36
- Applied to: `place_order` action at line 96-100
- Response: HTTP 429 (Too Many Requests) when limit exceeded

**Implementation:**
```php
if (!simplecart_checkRateLimit($clientIdentifier, 5, 300)) {
    http_response_code(429);
    throw new Exception('Rate limit exceeded. Please try again later.');
}
```

**Configuration:**
- Maximum requests: 5
- Time window: 300 seconds (5 minutes)
- Identifier: Client IP address

### 4. Input Validation & Sanitization (OWASP A03:2021 - Injection)

**Improvements:**
- **Email Validation:** Server-side format validation (`ajax.php` line 127-129)
- **Length Constraints:** Maximum lengths enforced for all customer fields (`ajax.php` lines 131-144)
- **Required Fields:** Name and email are mandatory (`ajax.php` lines 123-126)
- **Log Injection Prevention:** Debug logs sanitize newlines (`include/common.php` line 30)

**Field Limits:**
```php
$maxLengths = array(
    'name' => 100,
    'email' => 255,
    'phone' => 50,
    'address' => 500,
    'tablePreference' => 100,
    'shift' => 50,
    'helpendehanden' => 50
);
```

### 5. Supply Chain Security (OWASP A08:2021 - Software and Data Integrity Failures)

**Issue:** External CDN resources loaded without integrity verification.

**Fix Implemented:**
- **Subresource Integrity (SRI):** All external scripts/stylesheets include integrity hashes
  - Bulma CSS: `sha384-OLBgp1GsljhM2TJ+sbHjaiH9txEUvgdDTAzHv2P24donTt6/529l+9Ua0vFImLlb`
  - Vue 3.3.4: `sha384-Yqk6w7OMV5SRMLCaRKLcr3c1AwZ2ZdhPEWvjBJhZ3XvLLqZGLqJMZnGmKq8WVLqI`
  - QRCode.js: `sha384-wx+RZlCHnn3Tr6LYUI0txjbBjhfnZL8fCJM9T7LnfFEH74/4M5IgYnPFqV8LmqGF`
  
- **Version Pinning:** Specific versions used instead of latest tags
- **crossorigin="anonymous":** Prevents credential leakage

**Templates Updated:**
- `templates/simplecart_index.html`
- `templates/simplecart_checkout.html`

### 6. Security Headers (OWASP A05:2021 - Security Misconfiguration)

**Headers Added to AJAX Endpoint (`ajax.php` lines 17-23):**
```php
header('X-Content-Type-Options: nosniff');    // Prevent MIME sniffing
header('X-Frame-Options: DENY');               // Prevent clickjacking
header('X-XSS-Protection: 1; mode=block');     // Enable XSS filter
header('Referrer-Policy: strict-origin-when-cross-origin'); // Limit referrer info
```

### 7. Secure File Handling

**Protection Added:**
- **Debug Log Security:** Log file permissions set to 0640 (`include/common.php` line 34)
- **Apache .htaccess:** Denies direct access to log files (`src/.htaccess`)
- **Directory Listing:** Disabled via `.htaccess`

**.htaccess Rules:**
```apache
<Files "debug_email.log">
    Require all denied
</Files>
Options -Indexes
<FilesMatch "\.(log|bak|sql|ini|conf)$">
    Require all denied
</FilesMatch>
```

### 8. Information Disclosure Prevention

**Measures:**
- **SEPA QR Time Window:** QR codes only generated for orders < 24 hours old (`ajax.php` lines 256-260)
- **Timing Attack Mitigation:** 100ms delay on failed order lookups (`ajax.php` line 250)
- **Error Sanitization:** Generic error messages prevent information leakage
- **Log Injection Prevention:** Newlines stripped from log messages

## Deployment Checklist

### Before Deployment

1. **Disable Debug Mode in Production:**
   ```php
   // In src/include/common.php
   define('SIMPLECART_DEBUG_EMAIL', false);
   ```

2. **Configure SEPA Payment Details:**
   - Set beneficiary name, IBAN, BIC in module settings
   - Verify currency configuration (default: EUR)

3. **Review Rate Limiting:**
   - Default: 5 orders per 5 minutes per IP
   - Adjust in `ajax.php` simplecart_checkRateLimit() call if needed

4. **Verify File Permissions:**
   ```bash
   chmod 640 debug_email.log
   chmod 644 .htaccess
   ```

5. **Test CSRF Protection:**
   - Verify admin status changes use POST
   - Confirm tokens expire after 1 hour
   - Test token validation on expired tokens

### After Deployment

1. **Monitor Order Patterns:**
   - Watch for rate limit hits (HTTP 429 responses)
   - Review orders for unusual quantities or patterns
   - Check for failed validation attempts

2. **Review Logs Regularly:**
   - Check debug_email.log for email sending issues (if enabled)
   - Monitor web server error logs for exceptions
   - Look for repeated failed attempts (potential attacks)

3. **Update Dependencies:**
   - Keep ImpressCMS framework updated
   - Monitor security advisories for:
     - smhg/sepa-qr (composer dependency)
     - endroid/qr-code (composer dependency)
   - Update external CDN resources when security updates released

4. **Security Headers Verification:**
   - Use https://securityheaders.com to audit headers
   - Consider adding Content-Security-Policy (CSP) header
   - Add Strict-Transport-Security for HTTPS deployments

## Recommended Additional Hardening

### 1. Content Security Policy (CSP)

Add to main application headers (not module-specific):
```php
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://unpkg.com https://cdnjs.cloudflare.com; style-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:; font-src 'self'");
```

### 2. Database Security

ImpressCMS uses prepared statements by default, but verify:
- Database user has minimal required privileges
- Database password is strong and unique
- Database accessible only from localhost (if possible)

### 3. HTTPS Enforcement

In production:
```apache
# In .htaccess at document root
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 4. Session Security

ImpressCMS handles sessions, but verify:
- `session.cookie_httponly = 1` in php.ini
- `session.cookie_secure = 1` (HTTPS only)
- `session.cookie_samesite = "Lax"` or `"Strict"`

### 5. Email Security

For production email sending:
- Use authenticated SMTP (not mail() function)
- Implement SPF, DKIM, DMARC for domain
- Use TLS for SMTP connections

## Vulnerability Response

If a security issue is discovered:

1. **Report:** Email security contact or file private GitHub issue
2. **Document:** Record issue details, affected versions
3. **Patch:** Develop and test fix
4. **Disclose:** Coordinate disclosure timeline
5. **Update:** Release patched version with security advisory

## Compliance Notes

### GDPR Considerations
- Customer data stored in `customer_info` field (JSON)
- Implement data retention policy
- Provide data export functionality
- Enable data deletion on request

### PCI DSS
- Module does NOT handle credit card data
- SEPA payments are bank-transfer based
- No card storage or processing

### OWASP ASVS v4.0 Coverage
- **V1 Architecture:** Security architecture documented
- **V2 Authentication:** Relies on ImpressCMS auth
- **V3 Session:** Relies on ImpressCMS session handling
- **V4 Access Control:** CSRF protection implemented
- **V5 Input Validation:** Comprehensive validation added
- **V8 Error Handling:** Safe error messages
- **V10 Malicious Code:** SRI hashes added
- **V13 API:** Security headers added to AJAX endpoint
- **V14 Config:** Security configuration documented

## Testing Security Measures

### Manual Testing

1. **CSRF Protection:**
   ```bash
   # Test expired token
   curl -X POST https://example.com/modules/simplecart/ajax.php \
     -H "Content-Type: application/json" \
     -d '{"action":"place_order","token":"old_token","items":[],"customer":{}}'
   # Expected: "Security token invalid" or CSRF error
   ```

2. **Rate Limiting:**
   ```bash
   # Make 6 rapid order attempts
   for i in {1..6}; do
     curl -X POST https://example.com/modules/simplecart/ajax.php \
       -H "Content-Type: application/json" \
       -d '{"action":"place_order","token":"valid_token","items":[{"product_id":1,"quantity":1}],"customer":{"name":"Test","email":"test@example.com"}}'
   done
   # Expected: 6th request returns HTTP 429
   ```

3. **Quantity Bounds:**
   ```bash
   # Test excessive quantity
   curl -X POST https://example.com/modules/simplecart/ajax.php \
     -H "Content-Type: application/json" \
     -d '{"action":"place_order","token":"valid_token","items":[{"product_id":1,"quantity":9999}],"customer":{"name":"Test","email":"test@example.com"}}'
   # Expected: "Quantity exceeds maximum allowed"
   ```

### Automated Testing

Consider implementing:
- OWASP ZAP automated scans
- Burp Suite vulnerability assessment
- SQL injection tests (should be blocked by ImpressCMS)
- XSS tests (should be blocked by output escaping)

## References

- [OWASP Top 10 2021](https://owasp.org/Top10/)
- [OWASP ASVS 4.0](https://github.com/OWASP/ASVS)
- [ImpressCMS Security Guidelines](https://github.com/ImpressCMS/guidelines)
- [SEPA QR Code Standard (EPC069-12)](https://www.europeanpaymentscouncil.eu/document-library/guidance-documents/quick-response-code-guidelines-enable-data-capture-initiation)

## Changelog

### Version 0.08 (Security Hardening Release)
- Added time-limited CSRF tokens (1 hour expiry)
- Converted admin actions from GET to POST
- Implemented rate limiting (5 orders per 5 minutes)
- Added business logic validation (quantity bounds, empty order prevention)
- Added input validation and length constraints
- Added Subresource Integrity (SRI) hashes for external resources
- Added security headers (X-Content-Type-Options, X-Frame-Options, etc.)
- Added .htaccess protection for sensitive files
- Improved debug logging security
- Added timing attack mitigation for order lookups
- Added 24-hour QR code generation time window

---

**Last Updated:** 2026-01-14  
**Module Version:** 0.08  
**Security Review Status:** Comprehensive audit completed
