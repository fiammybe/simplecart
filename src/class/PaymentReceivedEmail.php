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
    private $customerPhone;
    private $customerShift;
    private $customerHelpendehanden;
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
        // Use 'n' format to get raw JSON without HTML decoding
        $customerInfo = (string)$this->order->getVar('customer_info', 'n');

        // Parse customer_info as JSON
        $customerData = json_decode($customerInfo, true);

        if (is_array($customerData)) {
            // Extract email and name from JSON
            $this->customerEmail = isset($customerData['email']) ? trim($customerData['email']) : '';
            $this->customerName = isset($customerData['name']) ? trim($customerData['name']) : '';
            $this->customerPhone = isset($customerData['phone']) ? trim($customerData['phone']) : '';
        } else {
            // JSON parsing failed
            $this->customerEmail = '';
            $this->customerName = '';
            $this->customerPhone = '';
        }

        // Extract shift and helpende_hand from order fields
        $this->customerShift = (string)$this->order->getVar('shift');
        $this->customerHelpendehanden = (string)$this->order->getVar('helpende_hand');
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

        // Customer Information Section
        $text .= str_repeat('-', 70) . "\n";
        $text .= _MD_SIMPLECART_EMAIL_CUSTOMER_INFO . "\n";
        $text .= str_repeat('-', 70) . "\n";
        if (!empty($this->customerName)) {
            $text .= _MD_SIMPLECART_NAME . ": " . $this->customerName . "\n";
        }
        if (!empty($this->customerEmail)) {
            $text .= _MD_SIMPLECART_EMAIL . ": " . $this->customerEmail . "\n";
        }
        if (!empty($this->customerPhone)) {
            $text .= _MD_SIMPLECART_PHONE . ": " . $this->customerPhone . "\n";
        }
        if (!empty($this->customerShift)) {
            $shiftText = $this->getShiftLabel($this->customerShift);
            $text .= _MD_SIMPLECART_ORDER_SHIFT . ": " . $shiftText . "\n";
        }
        if (!empty($this->customerHelpendehanden)) {
            $helpText = $this->getHelpLabel($this->customerHelpendehanden);
            $text .= _MD_SIMPLECART_HELP_MAIL . ": " . $helpText . "\n";
        }
        $text .= "\n";

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

    /**
     * Map shift value to translated label
     *
     * @param string $shift The shift value (e.g., "Shift 1", "Shift 2")
     * @return string The translated shift label
     */
    private function getShiftLabel($shift) {
        $shift = trim($shift);

        // Map shift values to language constants
        if (strpos($shift, '1') !== false) {
            return defined('_MD_SIMPLECART_ORDER_SHIFT_1') ? _MD_SIMPLECART_ORDER_SHIFT_1 : $shift;
        } elseif (strpos($shift, '2') !== false) {
            return defined('_MD_SIMPLECART_ORDER_SHIFT_2') ? _MD_SIMPLECART_ORDER_SHIFT_2 : $shift;
        }

        return $shift;
    }

    /**
     * Map helpende handen value to translated label
     *
     * @param string $help The helpende handen value
     * @return string The translated help label
     */
    private function getHelpLabel($help) {
        $help = trim($help);

        // Map help values to language constants
        if (strpos($help, '1') !== false) {
            return defined('_MD_SIMPLECART_HELP_1') ? _MD_SIMPLECART_HELP_1 : $help;
        } elseif (strpos($help, '2') !== false) {
            return defined('_MD_SIMPLECART_HELP_2') ? _MD_SIMPLECART_HELP_2 : $help;
        } elseif (strpos($help, '3') !== false) {
            return defined('_MD_SIMPLECART_HELP_3') ? _MD_SIMPLECART_HELP_3 : $help;
        } elseif (strpos($help, '4') !== false) {
            return defined('_MD_SIMPLECART_HELP_4') ? _MD_SIMPLECART_HELP_4 : $help;
        }

        return $help;
    }
}
?>

