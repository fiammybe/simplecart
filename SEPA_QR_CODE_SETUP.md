# SEPA QR Code Implementation for SimpleCart

## Overview

This implementation adds SEPA QR code generation functionality to the SimpleCart payment/checkout page. The QR code follows the EPC (European Payments Council) standard for SEPA Credit Transfers and can be scanned by banking applications to initiate payments.

## Components

### 1. SEPA QR Data Library
- **Location**: `src/class/lib/SepaQrData.php`
- **Source**: https://github.com/smhg/sepa-qr-data-php
- **Purpose**: Generates SEPA-compliant QR code data in EPC standard format
- **No Dependencies**: Standalone PHP class, no Composer required

### 2. SEPA QR Code Generator
- **Location**: `src/class/SepaQrCodeGenerator.php`
- **Purpose**: Wrapper class that uses SepaQrData to generate QR codes for orders
- **Configuration**: Accepts beneficiary name, IBAN, BIC, and currency

### 3. AJAX Endpoint
- **Action**: `sepa_qr_data`
- **Location**: `src/ajax.php`
- **Parameters**: `order_id` (required)
- **Response**: JSON with `qr_data` field containing EPC-formatted SEPA data

### 4. Frontend Implementation
- **QR Code Library**: qrcode.js (CDN-based, no server dependencies)
- **Location**: `src/assets/js/cart.js`
- **Template**: `src/templates/simplecart_checkout.html`

## Configuration

To enable SEPA QR code generation, configure the following in your code:

```php
$config = array(
    'beneficiary_name' => 'Your Shop Name',
    'beneficiary_iban' => 'DE89370400440532013000',  // Your IBAN
    'beneficiary_bic' => 'COBADEFFXXX',              // Your BIC (optional)
    'currency' => 'EUR'                              // Currency code
);
```

Update `src/ajax.php` line ~105 with your actual SEPA payment details.

## How It Works

1. **Order Placement**: User completes checkout and places order
2. **QR Data Generation**: Server generates SEPA-compliant QR data via AJAX
3. **QR Code Rendering**: Client-side JavaScript renders QR code using qrcode.js
4. **Display**: QR code shown to user with payment instructions

## SEPA QR Code Data Format

The generated QR code contains:
- Service Tag: BCD
- Version: 2
- Character Set: UTF-8
- Identification: SCT (SEPA Credit Transfer)
- Beneficiary BIC (if configured)
- Beneficiary Name
- Beneficiary IBAN
- Amount (in EUR)
- Purpose Code (optional)
- Remittance Reference (optional)
- Remittance Text: Order #[OrderID]
- Beneficiary Information (optional)

## Language Strings

Added language constants:
- `_MD_SIMPLECART_PAY_WITH_SEPA`: "Pay with Bank Transfer QR Code"
- `_MD_SIMPLECART_SCAN_TO_PAY`: "Scan this QR code with your banking app..."
- `_MD_SIMPLECART_ORDER_SUCCESS`: "Order placed successfully"
- `_MD_SIMPLECART_ORDER_ID`: "Order ID"

## Testing

1. Place an order through the checkout process
2. After successful order placement, the SEPA QR code should appear
3. Scan the QR code with a banking app to verify payment details
4. Check that order ID is included in the remittance text

## Browser Compatibility

- Works in all modern browsers (Chrome, Firefox, Safari, Edge)
- Requires JavaScript enabled
- QR code library (qrcode.js) is loaded from CDN

## Security Notes

- SEPA data is generated server-side and validated
- Order ID is verified before generating QR code
- CSRF protection is maintained through existing token system
- No sensitive data is exposed in QR code beyond payment details

## Files Modified

1. `src/ajax.php` - Added sepa_qr_data action
2. `src/assets/js/cart.js` - Added QR code generation logic
3. `src/templates/simplecart_checkout.html` - Added QR code display section
4. `src/language/english/main.php` - Added language strings

## Files Added

1. `src/class/lib/SepaQrData.php` - SEPA QR data library
2. `src/class/SepaQrCodeGenerator.php` - QR code generator wrapper

