<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

/**
 * OrderConfirmationEmail - Generates plain text confirmation emails for orders
 */

class OrderConfirmationEmail {
    private $order;
    private $orderItems;
    private $customerEmail;
    private $customerName;
    private $sepaConfig;
    private $currency;

    public function __construct($order, $orderItems, $sepaConfig = array(), $currency = 'EUR') {
        $this->order = $order;
        $this->orderItems = $orderItems;
        $this->sepaConfig = $sepaConfig;
        $this->currency = $currency;

        // Extract customer info from order
        $this->extractCustomerInfo();
    }

    private function extractCustomerInfo() {
        $customerInfo = (string)$this->order->getVar('customer_info');

        // Decode HTML entities (setVar() HTML-encodes the data)
        $customerInfoDecoded = html_entity_decode($customerInfo, ENT_QUOTES, 'UTF-8');

        // Parse customer_info as JSON
        $customerData = json_decode($customerInfoDecoded, true);

        if (is_array($customerData)) {
            // Extract email and name from JSON
            $this->customerEmail = isset($customerData['email']) ? trim($customerData['email']) : '';
            $this->customerName = isset($customerData['name']) ? trim($customerData['name']) : '';
        } else {
            // JSON parsing failed
            $this->customerEmail = '';
            $this->customerName = '';
        }
    }

    public function getCustomerEmail() {
        return $this->customerEmail;
    }

    public function getSubject() {
        $orderId = (int)$this->order->getVar('order_id');
        return sprintf(_MD_SIMPLECART_EMAIL_SUBJECT, $orderId);
    }

    public function getTextContent() {
        $orderId = (int)$this->order->getVar('order_id');
        $totalAmount = (float)$this->order->getVar('total_amount');
        $timestamp = (int)$this->order->getVar('timestamp');
        $orderDate = date('Y-m-d H:i:s', $timestamp);

        $text = '';
        $text .= str_repeat('=', 70) . "\n";
        $text .= _MD_SIMPLECART_EMAIL_THANK_YOU . "\n";
        $text .= str_repeat('=', 70) . "\n\n";

        $text .= _MD_SIMPLECART_EMAIL_GREETING . " " . $this->customerName . "\n\n";

        // Order Details Section
        $text .= str_repeat('-', 70) . "\n";
        $text .= _MD_SIMPLECART_EMAIL_ORDER_DETAILS . "\n";
        $text .= str_repeat('-', 70) . "\n";
        $text .= _MD_SIMPLECART_ORDER_ID . ": #" . $orderId . "\n";
        $text .= _MD_SIMPLECART_EMAIL_ORDER_DATE . ": " . $orderDate . "\n\n";

        // Items Section
        $text .= str_repeat('-', 70) . "\n";
        $text .= _MD_SIMPLECART_EMAIL_ITEMS . "\n";
        $text .= str_repeat('-', 70) . "\n";

        // Column headers
        $text .= sprintf("%-35s %12s %8s %12s\n",
            _MD_SIMPLECART_NAME,
            _MD_SIMPLECART_EMAIL_UNIT_PRICE,
            _MD_SIMPLECART_EMAIL_QUANTITY,
            _MD_SIMPLECART_EMAIL_SUBTOTAL
        );
        $text .= str_repeat('-', 70) . "\n";

        // Items
        foreach ($this->orderItems as $item) {
            $name = (string)$item->getVar('product_name');
            $price = (float)$item->getVar('product_price');
            $qty = (int)$item->getVar('quantity');
            $subtotal = $qty * $price;

            $text .= sprintf("%-35s %12s %8d %12s\n",
                substr($name, 0, 35),
                $this->formatCurrency($price),
                $qty,
                $this->formatCurrency($subtotal)
            );
        }

        // Total
        $text .= str_repeat('-', 70) . "\n";
        $text .= sprintf("%-35s %12s %8s %12s\n",
            _MD_SIMPLECART_TOTAL . ":",
            "",
            "",
            $this->formatCurrency($totalAmount)
        );
        $text .= str_repeat('=', 70) . "\n\n";

        // Payment Information Section
        if (!empty($this->sepaConfig['beneficiary_iban'])) {
            $text .= str_repeat('-', 70) . "\n";
            $text .= _MD_SIMPLECART_PAYMENT_INFO . "\n";
            $text .= str_repeat('-', 70) . "\n";
            $text .= _MD_SIMPLECART_BENEFICIARY . ": " . $this->sepaConfig['beneficiary_name'] . "\n";
            $text .= _MD_SIMPLECART_IBAN . ": " . $this->sepaConfig['beneficiary_iban'] . "\n";
            if (!empty($this->sepaConfig['beneficiary_bic'])) {
                $text .= "BIC: " . $this->sepaConfig['beneficiary_bic'] . "\n";
            }
            $text .= _MD_SIMPLECART_AMOUNT . ": " . $this->formatCurrency($totalAmount) . "\n\n";
            $text .= "Please use the SEPA QR code provided in the checkout page to complete your payment.\n";
            $text .= str_repeat('-', 70) . "\n\n";
        }

        // Footer
        $text .= _MD_SIMPLECART_EMAIL_FOOTER . "\n\n";
        $text .= str_repeat('=', 70) . "\n";

        return $text;
    }

    private function formatCurrency($amount) {
        return number_format((float)$amount, 2, '.', ',') . ' ' . $this->currency;
    }
}
?>

