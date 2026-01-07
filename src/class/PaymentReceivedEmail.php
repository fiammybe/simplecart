<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

/**
 * PaymentReceivedEmail - Generates payment received notification emails
 * Reuses the order overview template from OrderConfirmationEmail
 */

class PaymentReceivedEmail {
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
        return sprintf(_MD_SIMPLECART_PAYMENT_RECEIVED_SUBJECT, $orderId);
    }

    public function getTextContent() {
        $orderId = (int)$this->order->getVar('order_id');
        $totalAmount = (float)$this->order->getVar('total_amount');
        // Get the pre-formatted timestamp from ImpressCMS XOBJ_DTYPE_LTIME
        $orderDate = (string)$this->order->getVar('timestamp');

        $text = '';
        $text .= str_repeat('=', 70) . "\n";
        $text .= _MD_SIMPLECART_PAYMENT_RECEIVED_HEADING . "\n";
        $text .= str_repeat('=', 70) . "\n\n";

        $text .= _MD_SIMPLECART_EMAIL_GREETING . " " . $this->customerName . "\n\n";
        $text .= sprintf(_MD_SIMPLECART_PAYMENT_RECEIVED_MESSAGE, $orderId) . "\n\n";

        // Order Details Section (reused from confirmation email)
        $text .= str_repeat('-', 70) . "\n";
        $text .= _MD_SIMPLECART_EMAIL_ORDER_DETAILS . "\n";
        $text .= str_repeat('-', 70) . "\n";
        $text .= _MD_SIMPLECART_ORDER_ID . ": #" . $orderId . "\n";
        $text .= _MD_SIMPLECART_EMAIL_ORDER_DATE . ": " . $orderDate . "\n\n";

        // Items Section (reused from confirmation email)
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

